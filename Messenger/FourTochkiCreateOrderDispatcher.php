<?php
/*
 * Copyright 2026.  Baks.dev <admin@baks.dev>
 *
 *  Permission is hereby granted, free of charge, to any person obtaining a copy
 *  of this software and associated documentation files (the "Software"), to deal
 *  in the Software without restriction, including without limitation the rights
 *  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *  copies of the Software, and to permit persons to whom the Software is furnished
 *  to do so, subject to the following conditions:
 *
 *  The above copyright notice and this permission notice shall be included in all
 *  copies or substantial portions of the Software.
 *
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *  FITNESS FOR A PARTICULAR PURPOSE AND NON INFRINGEMENT. IN NO EVENT SHALL THE
 *  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 *  THE SOFTWARE.
 */

declare(strict_types=1);

namespace BaksDev\FourTochki\Orders\Messenger;

use BaksDev\Core\Deduplicator\DeduplicatorInterface;
use BaksDev\FourTochki\Orders\Api\New\FourTochkiCreateOrderRequest;
use BaksDev\FourTochki\Orders\Entity\FourTochkiOrder;
use BaksDev\FourTochki\Orders\UseCase\NewEdit\NewEditFourTochkiOrderDTO;
use BaksDev\FourTochki\Orders\UseCase\NewEdit\NewEditFourTochkiOrderHandler;
use BaksDev\FourTochki\Products\Entity\FourTochkiProduct;
use BaksDev\FourTochki\Products\Repository\FourTochkiProductProfile\FourTochkiProductProfileInterface;
use BaksDev\FourTochki\Repository\AllFourTochkiAuth\AllFourTochkiAuthInterface;
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Entity\Products\OrderProduct;
use BaksDev\Orders\Order\Messenger\OrderMessage;
use BaksDev\Orders\Order\Repository\CurrentOrderEvent\CurrentOrderEventInterface;
use BaksDev\Orders\Order\Type\Status\OrderStatus\Collection\OrderStatusNew;
use BaksDev\Products\Product\Repository\CurrentProductIdentifier\CurrentProductIdentifierByEventInterface;
use BaksDev\Products\Product\Repository\CurrentProductIdentifier\CurrentProductIdentifierResult;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(priority: 0)]
final readonly class FourTochkiCreateOrderDispatcher
{
    public function __construct(
        #[Target('fourTochkiOrdersLogger')] private LoggerInterface $Logger,
        private CurrentOrderEventInterface $CurrentOrderEventRepository,
        private AllFourTochkiAuthInterface $AllFourTochkiAuthRepository,
        private FourTochkiProductProfileInterface $FourTochkiProductProfileRepository,
        private CurrentProductIdentifierByEventInterface $CurrentProductIdentifierByEventRepository,
        private FourTochkiCreateOrderRequest $FourTochkiCreateOrderRequest,
        private NewEditFourTochkiOrderHandler $NewEditFourTochkiOrderHandler,
        private DeduplicatorInterface $Deduplicator,
        private EntityManagerInterface $EntityManager,
    ) {}

    public function __invoke(OrderMessage $message): void
    {
        $Deduplicator = $this->Deduplicator
            ->namespace('four-tochki-orders')
            ->deduplication([
                (string) $message->getId(),
                self::class,
            ]);

        if($Deduplicator->isExecuted())
        {
            return;
        }


        /** Находим событие заказа */
        $OrderEvent = $this->CurrentOrderEventRepository
            ->forOrder($message->getId())
            ->find();

        if(false === ($OrderEvent instanceof OrderEvent))
        {
            $this->Logger->critical(
                sprintf('Текущее событие для заказа %s не было найдено', $message->getId()),
                [var_export($message, true), self::class.':'.__LINE__]
            );
            return;
        }


        /**
         * Если статус заказа не Статус New «Новый» - завершаем обработчик
         */
        if(false === $OrderEvent->isStatusEquals(OrderStatusNew::class))
        {
            $this->Logger->warning(
                sprintf('Заказ %s не находится в статусе "Новый"', $message->getId()),
                [var_export($message, true), self::class.':'.__LINE__]
            );
            return;
        }


        if(false === ($OrderEvent->getOrderProfile() instanceof UserProfileUid))
        {
            $this->Logger->critical(
                sprintf('У заказа %s не указан профиль', $message->getId()),
                [var_export($message, true), self::class.':'.__LINE__]
            );
            return;
        }


        /** Проверяем наличие токена в профиле - если его нет, заказ создавать не нужно */
        $authorization = $this->AllFourTochkiAuthRepository
            ->profile($OrderEvent->getOrderProfile())
            ->findPaginator();

        if(empty($authorization->getData()))
        {
            return;
        }


        /** Проверяем, не был ли ранее в базе сохранен такой заказ для Форточек */
        $FourTochkiOrder = $this->EntityManager
            ->getRepository(FourTochkiOrder::class)
            ->findBy(['ord' => $message->getId()]);

        if(false === empty($FourTochkiOrder))
        {
            $this->Logger->warning(
                sprintf('Для заказа %s уже был создан заказ на 4tochki', $message->getId()),
                [var_export($message, true), self::class.':'.__LINE__]
            );
            return;
        }


        /** Получаем продукты данного заказа */
        $products = $OrderEvent->getProduct();


        /** @var OrderProduct $product */
        foreach($products as $product)
        {
            /** Находим идентификаторы продукта */
            $CurrentProductIdentifier = $this->CurrentProductIdentifierByEventRepository
                ->forEvent($product->getProduct())
                ->forOffer($product->getOffer())
                ->forVariation($product->getVariation())
                ->forModification($product->getModification())
                ->find();

            if(false === ($CurrentProductIdentifier instanceof CurrentProductIdentifierResult))
            {
                $this->Logger->critical(
                    sprintf('Не удалось найти идентификаторы события продукта %s', $product->getId()),
                    [var_export($message, true), self::class.':'.__LINE__]
                );
                continue;
            }


            /**
             * Проверяем наличие настройки 4tochki для данного продукта и создаем заказ только для тех, у которых эта
             * настройка есть
             */
            $FourTochkiProductProfileResult = $this->FourTochkiProductProfileRepository
                ->product($CurrentProductIdentifier->getProduct())
                ->offerConst($CurrentProductIdentifier->getOfferConst())
                ->variationConst($CurrentProductIdentifier->getVariationConst())
                ->modificationConst($CurrentProductIdentifier->getModificationConst())
                ->find();

            if(false === ($FourTochkiProductProfileResult instanceof FourTochkiProduct))
            {
                continue;
            }


            /** Отправляем Api-запрос на создание заказа */
            $response = $this->FourTochkiCreateOrderRequest
                ->profile($OrderEvent->getOrderProfile())
                ->setOrderNumber($OrderEvent->getPostingNumber())
                ->createOrder($FourTochkiProductProfileResult->getCode(), $product->getTotal());

            if(false === $response)
            {
                $this->Logger->critical(
                    'Ошибка создания заказа',
                    [var_export($message, true), self::class.':'.__LINE__]
                );
                continue;
            }

            $this->Logger->info('Успешно создали заказ', [var_export($message, true), self::class.':'.__LINE__]);

            $Deduplicator->save();


            /** Сохраняем идентификатор созданного заказа в базу */
            $NewEditFourTochkiOrderDTO = new NewEditFourTochkiOrderDTO($response, $message->getId());
            $handle = $this->NewEditFourTochkiOrderHandler->handle($NewEditFourTochkiOrderDTO);

            if(false === ($handle instanceof FourTochkiOrder))
            {
                $this->Logger->critical(
                    'Ошибка сохранения в базу идентификатора заказа 4tochki',
                    [var_export($NewEditFourTochkiOrderDTO, true), self::class.':'.__LINE__]
                );
            }
        }
    }
}