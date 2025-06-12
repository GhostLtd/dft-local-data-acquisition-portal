<?php

namespace App\Form\Type\Admin;

use Ghost\GovUkCoreBundle\Form\ConfirmActionType;
use Ghost\GovUkFrontendBundle\Form\Type\CheckboxType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class ConfirmReleaseReturnsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('confirm', CheckboxType::class, [
            'label' => 'pages.release_returns.confirmation',
            'translation_domain' => 'admin',
            'constraints' => [
                new NotBlank(message: 'release_returns.confirm.not_blank')
            ],
            'priority' => 10,
        ]);
    }

    public function getParent(): ?string
    {
        return ConfirmActionType::class;
    }
}
