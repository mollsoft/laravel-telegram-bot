<?php

namespace Mollsoft\Telegram\DTO;

use Mollsoft\Telegram\Abstract\DTO;

class Contact extends DTO
{
   protected function required(): array
   {
       return ['phone_number', 'first_name'];
   }

   public function phoneNumber(): string
   {
       return $this->getOrFail('phone_number');
   }

   public function firstName(): string
   {
       return $this->getOrFail('first_name');
   }

   public function lastName(): ?string
   {
       return $this->get('last_name');
   }

   public function userId(): ?string
   {
       $userId = $this->get('user_id');

       return $userId !== null ? (string)$userId : null;
   }
}
