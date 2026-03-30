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

namespace BaksDev\FourTochki\Orders\UseCase\NewEdit\Tests;

use BaksDev\FourTochki\Orders\Entity\FourTochkiOrder;
use BaksDev\FourTochki\Orders\UseCase\NewEdit\NewEditFourTochkiOrderDTO;
use BaksDev\FourTochki\Orders\UseCase\NewEdit\NewEditFourTochkiOrderHandler;
use BaksDev\Orders\Order\Type\Id\OrderUid;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;

#[Group('four-tochki-orders')]
#[Group('four-tochki-orders-usecase')]
#[When(env: 'test')]
class NewEditFourTochkiOrderHandlerTest extends KernelTestCase
{
    public function testUseCase(): void
    {
        /** @see NewEditFourTochkiOrderDTO */
        $NewEditFourTochkiOrderDTO = new NewEditFourTochkiOrderDTO(123, new OrderUid());

        /** @var NewEditFourTochkiOrderHandler $NewEditFourTochkiOrderHandler */
        $NewEditFourTochkiOrderHandler = self::getContainer()->get(NewEditFourTochkiOrderHandler::class);
        $handle = $NewEditFourTochkiOrderHandler->handle($NewEditFourTochkiOrderDTO);

        self::assertTrue(($handle instanceof FourTochkiOrder), $handle.': Ошибка ');
    }


    public static function tearDownAfterClass(): void
    {
        $EntityManager = self::getContainer()->get(EntityManagerInterface::class);

        $FourTochkiOrder = $EntityManager->getRepository(FourTochkiOrder::class)->find(123);

        if($FourTochkiOrder)
        {
            $EntityManager->remove($FourTochkiOrder);
        }

        $EntityManager->flush();
        $EntityManager->clear();
    }
}