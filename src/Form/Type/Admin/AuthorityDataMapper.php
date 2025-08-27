<?php

namespace App\Form\Type\Admin;

use App\Entity\Authority;
use App\Entity\User;
use App\Form\Type\FilteringDataMapper;
use Symfony\Component\Form\FormInterface;

class AuthorityDataMapper extends FilteringDataMapper
{
    public function __construct(protected FundAwardDataMapper $fundAwardDataMapper)
    {
        parent::__construct();
    }

    /**
     * @param Authority $data
     */
    public function mapDataToForms(mixed $data, \Traversable $forms): void
    {
        parent::mapDataToForms($data, $this->filterForms($forms, exclude: ['funds']));

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
        parent::mapFormsToData($this->filterForms($forms, exclude: ['funds']), $data);

        if (!$data->getId()) {
            $this->fundAwardDataMapper->mapFormsToData($forms, $data);
        }

        $forms = iterator_to_array($forms);
        /** @var FormInterface[] $forms */

        if ($data->getId() && $forms['admin_choice']->getData() === AuthorityType::ADMIN_CHOICE_EXISTING) {
            $data->setAdmin($forms['existing_admin']->getData());
        }
    }
}
