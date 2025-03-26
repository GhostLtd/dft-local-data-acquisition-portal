<?php

namespace App\Form\Type\FundReturn\Crsts;

use App\Entity\Enum\Rating;
use App\Entity\FundReturn\CrstsFundReturn;
use App\Form\Type\BaseButtonsFormType;
use Ghost\GovUkFrontendBundle\Form\Type\ChoiceType;
use Ghost\GovUkFrontendBundle\Form\Type\TextareaType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Twig\Environment;

class OverallProgressType extends AbstractType
{
    public function __construct(protected Environment $twig) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('progressSummary', TextareaType::class, [
                'label' => 'forms.crsts.overall_progress.progress_summary.label',
                'label_attr' => ['class' => 'govuk-label--s']
            ])
            ->add('deliveryConfidence', TextareaType::class, [
                'label' => 'forms.crsts.overall_progress.delivery_confidence.label',
                'label_attr' => ['class' => 'govuk-label--s']
            ])
            ->add('overallConfidence', ChoiceType::class, [
                'label' => 'forms.crsts.overall_progress.overall_confidence.label',
                'choices' => Rating::cases(),
                'choice_label' => fn(Rating $choice) => "enum.rating.{$choice->value}",
                'choice_value' => fn(?Rating $choice) => $choice?->value,
                'label_attr' => ['class' => 'govuk-fieldset__legend--s'],
                'help' => $this->twig->render('frontend/includes/rag_guidance_help.html.twig'),
                'help_html' => true,
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
            'validation_groups' => ['overall_progress'],
        ]);
    }
}
