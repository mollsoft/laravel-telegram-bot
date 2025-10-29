<?php

namespace Mollsoft\Telegram\DTO;

use Mollsoft\Telegram\Abstract\DTO;

class Contact extends DTO
{
   protected function required(): array
   {
       return ['phone_number'];
   }

   public function phoneNumber(): string
   {
       return $this->getOrFail('phone_number');
   }

   public function firstName(): string
   {
       return $this->get('first_name') ?: '?';
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
