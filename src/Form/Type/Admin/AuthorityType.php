<?php

namespace App\Form\Type\Admin;

use App\Entity\Authority;
use App\Form\Type\BaseButtonsFormType;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AuthorityType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', Gds\InputType::class, [
                'label' => 'authority.form.name',
                'label_attr' => ['class' => 'govuk-label--m'],
            ])
            ->add('admin', UserType::class, [
                'label' => 'authority.form.admin',
                'label_attr' => ['class' => 'govuk-label--m'],
            ])
            ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Authority::class,
            'translation_domain' => 'admin',
        ]);
    }

    public function getParent(): string
    {
        return BaseButtonsFormType::class;
    }
}
