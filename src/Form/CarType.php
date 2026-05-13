<?php

namespace App\Form;

use App\Entity\Car;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;


class CarType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('brand')
            ->add('model')
            ->add('registrationNumber')
            ->add('pricePerDay')
            ->add('image')
            ->add('status', ChoiceType::class, [
                'choices' => [
                    '🟢 Available' => 'available',
                    '🔴 Unavailable' => 'unavailable',
                ],
                'expanded' => true,   // 👈 makes it radio buttons
                'multiple' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Car::class,
        ]);
    }
}
