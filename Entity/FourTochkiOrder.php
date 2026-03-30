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

namespace BaksDev\FourTochki\Orders\Entity;

use BaksDev\Core\Entity\EntityState;
use BaksDev\Orders\Order\Type\Id\OrderUid;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'four_tochki_order')]
class FourTochkiOrder extends EntityState
{
    /**
    * Идентификатор заказа в системе Форточки
    */
    #[Assert\NotBlank]
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    private int $id;

	/**
	* Внутренний идентификатор заказа
	*/
	#[Assert\NotBlank]
    #[Assert\Uuid]
	#[ORM\Column(type: OrderUid::TYPE, unique: true)]
	private OrderUid $ord;

    public function __construct(int $id)
    {
        $this->id = $id;
    }

	public function __toString(): string
    {
        return (string) $this->id;
    }

	/**
	* Идентификатор заказа в системе Форточки
	*/
	public function getId(): int
	{
		return $this->id;
	}

	/**
	* Внутренний идентификатор
	*/
	public function getOrd(): OrderUid
	{
		return $this->ord;
	}

    public function setOrd(OrderUid $order): self
    {
        $this->ord = $order;
        return $this;
    }
}