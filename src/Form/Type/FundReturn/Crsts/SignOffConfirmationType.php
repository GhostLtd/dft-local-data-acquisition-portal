<?php

namespace App\Form\Type\FundReturn\Crsts;

use Ghost\GovUkCoreBundle\Form\ConfirmActionType;
use Ghost\GovUkFrontendBundle\Form\Type\CheckboxType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class SignOffConfirmationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('confirm_fco_approved', CheckboxType::class, [
                'label' => 'frontend.pages.fund_return_signoff.confirm_fco_approved.label',
                'constraints' => [
                    new NotBlank(message: 'fund_return_signoff.confirm_fco_approved.not_blank')
                ],
                'priority' => 10,
            ])
            ->add('confirm_cannot_be_undone', CheckboxType::class, [
                'label' => 'frontend.pages.fund_return_signoff.confirm_cannot_be_undone.label',
                'constraints' => [
                    new NotBlank(message: 'fund_return_signoff.confirm_cannot_be_undone.not_blank')
                ],
                'priority' => 10,
            ]);
    }

    public function getParent(): ?string
    {
        return ConfirmActionType::class;
    }
}
