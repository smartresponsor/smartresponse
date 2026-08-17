<?php

declare(strict_types=1);

namespace App\Form\Placement;

use App\Dto\Placement\AppFulfillmentPlacementFormData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class AppFulfillmentPlacementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('mode', ChoiceType::class, ['choices' => [
                'On-site' => 'onsite', 'Remote' => 'remote', 'Hybrid' => 'hybrid',
                'Shipping' => 'shipping', 'Pickup' => 'pickup', 'Digital delivery' => 'digital',
            ]])
            ->add('serviceArea', TextType::class, ['required' => false, 'label' => 'Service area / ZIP coverage'])
            ->add('radiusKm', NumberType::class, ['required' => false, 'label' => 'Service radius (km)'])
            ->add('weightKg', NumberType::class, ['required' => false, 'label' => 'Shipment weight (kg)'])
            ->add('priority', ChoiceType::class, [
                'required' => false,
                'placeholder' => 'Not applicable',
                'choices' => ['Standard' => 'STANDARD', 'Express' => 'EXPRESS', 'Overnight' => 'OVERNIGHT'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => AppFulfillmentPlacementFormData::class, 'csrf_protection' => true]);
    }
}
