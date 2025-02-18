<?php

namespace App\Form\Type\Admin;

use App\Entity\Authority;
use App\Entity\User;
use Symfony\Component\Form\Extension\Core\DataMapper\DataMapper;
use Symfony\Component\Form\FormInterface;

class AuthorityDataMapper extends DataMapper
{
    /**
     * @param Authority $data
     */
    public function mapDataToForms(mixed $data, \Traversable $forms): void
    {
        parent::mapDataToForms($data, $forms);

        $forms = iterator_to_array($forms);
        /** @var FormInterface[] $forms */

        if ($data->getId()) {
            $forms['admin_choice']->setData(AuthorityType::ADMIN_CHOICE_EXISTING);
            $forms['existing_admin']->setData($data->getAdmin());
            $forms['admin']->setData(new User());
        }
    }

    /**
     * @param Authority $data
     */
    public function mapFormsToData(\Traversable $forms, mixed &$data): void
    {
        parent::mapFormsToData($forms, $data);

        $forms = iterator_to_array($forms);
        /** @var FormInterface[] $forms */

        if ($data->getId() && $forms['admin_choice']->getData() === AuthorityType::ADMIN_CHOICE_EXISTING) {
            $data->setAdmin($forms['existing_admin']->getData());
        }
    }
}
