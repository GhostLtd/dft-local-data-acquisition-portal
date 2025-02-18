<?php

namespace App\Form\Type\Admin;

use App\Entity\Authority;
use App\Entity\User;
use App\Form\Type\BaseButtonsFormType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityRepository;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
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
            ->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
                /** @var Authority $data */
                $data = $event->getData();
                $form = $event->getForm();
                if ($data->getId()) {
                    $form->add('admin', Gds\EntityType::class, [
                        'class' => User::class,
                        'query_builder' => function (UserRepository $er) use ($data) {return $er->getAllForAuthorityQueryBuilder($data);},
                        'choice_label' => 'name',
                        'label' => 'authority.form.admin',
                        'label_attr' => ['class' => 'govuk-label--m'],
                    ]);
                } else {
                    $form->add('admin', UserType::class, [
                        'label' => 'authority.form.admin',
                        'label_attr' => ['class' => 'govuk-label--m'],
                    ]);
                }

            })
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
