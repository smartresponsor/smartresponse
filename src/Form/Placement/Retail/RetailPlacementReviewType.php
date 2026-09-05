<?php

declare(strict_types=1);

namespace App\Form\Placement\Retail;

use App\Dto\Placement\Retail\RetailPlacementReviewFormData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class RetailPlacementReviewType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, ['disabled' => true])
            ->add('kind', TextType::class, ['disabled' => true])
            ->add('catalog', TextType::class, ['disabled' => true])
            ->add('typePath', TextType::class, ['disabled' => true, 'label' => 'Type'])
            ->add('location', TextareaType::class, ['disabled' => true])
            ->add('fulfillment', TextareaType::class, ['disabled' => true])
            ->add('pricing', TextareaType::class, ['disabled' => true])
            ->add('publish', CheckboxType::class, ['required' => true, 'label' => 'Publish this listing']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => RetailPlacementReviewFormData::class, 'csrf_protection' => true]);
    }
}
