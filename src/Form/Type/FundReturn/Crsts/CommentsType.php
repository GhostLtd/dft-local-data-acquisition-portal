<?php

namespace App\Form\Type\FundReturn\Crsts;

use App\Entity\FundReturn\CrstsFundReturn;
use App\Form\Type\BaseButtonsFormType;
use Ghost\GovUkFrontendBundle\Form\Type\TextareaType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CommentsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('comments', TextareaType::class, [
            'label' => 'forms.crsts.comments.comments.label',
            'help' => 'forms.crsts.comments.comments.help',
            'label_attr' => ['class' => 'govuk-label--s']
        ]);
    }

    public function getParent(): string
    {
        return BaseButtonsFormType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CrstsFundReturn::class,
            'validation_groups' => ['comments'],
        ]);
    }
}
