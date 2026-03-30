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

namespace BaksDev\FourTochki\Orders\Api\New\Tests;

use BaksDev\FourTochki\Orders\Api\New\FourTochkiCreateOrderRequest;
use BaksDev\FourTochki\Repository\FourTochkiAuthorizationByProfile\FourTochkiAuthorizationByProfileInterface;
use BaksDev\FourTochki\Repository\FourTochkiAuthorizationByProfile\FourTochkiAuthorizationByProfileRepository;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
#[Group('four-tochki-orders')]
#[Group('four-tochki-orders-api')]
final class FourTochkiCreateOrderRequestTest extends KernelTestCase
{
    public function testApi(): void
    {
        $FourTochkiCreateOrderRequest = self::getContainer()->get(FourTochkiCreateOrderRequest::class);

        $FourTochkiAuthorizationByProfileRepository = self::getContainer()
            ->get(FourTochkiAuthorizationByProfileInterface::class);

        $profile = $_SERVER['TEST_PROFILE'] ?? UserProfileUid::TEST;


        /**
         * Получаем данные для авторизации
         * @var FourTochkiAuthorizationByProfileRepository $FourTochkiAuthorizationByProfileRepository
         */
        $authorization = $FourTochkiAuthorizationByProfileRepository->getAuthorization(new UserProfileUid($profile));


        /** @var FourTochkiCreateOrderRequest $FourTochkiCreateOrderRequest */
        $result = $FourTochkiCreateOrderRequest
            ->authorization($authorization)
            ->setOrderNumber('test')
            ->isTest()
            ->createOrder('CTS287312', 1);

        self::assertIsInt($result);
    }
}