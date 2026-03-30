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

namespace BaksDev\FourTochki\Orders\Api\New;

use BaksDev\FourTochki\Api\FourTochkiApi;
use InvalidArgumentException;

final class FourTochkiCreateOrderRequest extends FourTochkiApi
{
    const string METHOD = 'CreateOrder';

    private bool $test = false;

    private ?string $orderNumber = null;

    /** Вызываем функцию, если планируем создать тестовый заказ */
    public function isTest(): self
    {
        $this->test = true;
        return $this;
    }

    public function setOrderNumber(?string $number): self
    {
        $this->orderNumber = $number;
        return $this;
    }


    /**
     * Метод создает новый заказ на определенный продукт в указанном количестве и возвращает идентификатор созданного
     * заказа
     *
     * @see https://b2b.4tochki.ru/Help/Page?url=CreateOrder.html
     *
     * is_test (bool) - Признак, что заказ тестовый. Возможные значения: True – если заказ тестовый, иначе False.
     * Тестовый заказ создаётся в статусе "я формирую", иначе заказ сразу же отправляется в 1С для резервирования
     * товаров.
     * product_list ([ArrayOfOrderProduct]) - Ассортимент заказа.
     * base_order ([BaseOrder]) - Параметры заказа в вашей системе, на основании которого вы создаёте заказ в Фортчоках.
     *
     * ArrayOfOrderProduct
     * code (string) - Код товара в системе ПИШ.
     * quantity (int) - Количество.
     * wrh (int) - ID склада. Возможные значения. Если склад не указан, то система подберёт ближайший склад (по
     * логистике), на котором есть данный товар.
     *
     * BaseOrder
     * orderNumber	string	Номер заказа в вашей системе.
     */
    public function createOrder(string $code, int $quantity): false|int
    {
        /** В тестовой среде создаем только тестовые заказы */
        if(false === $this->isExecuteEnvironment())
        {
            $this->test = true;
        }

        if(false === $this->getWarehouse())
        {
            throw new InvalidArgumentException('Не указан идентификатор склада');
        }

        if(false === is_string($this->orderNumber))
        {
            throw new InvalidArgumentException('Не указан номер заказа (постинг)');
        }

        $response = $this->tokenHttpClient(
            method: self::METHOD,
            options: ['order' => [
                'base_order' => ['orderNumber' => $this->orderNumber],
                'product_list' => [['code' => $code, 'quantity' => $quantity, 'wrh' => $this->getWarehouse()]],
                'is_test' => $this->test
            ]],
        );

        if(false === $response->CreateOrderResult->success || false === isset($response->CreateOrderResult->orderID))
        {
            return false;
        }

        return $response->CreateOrderResult->orderID;
    }
}