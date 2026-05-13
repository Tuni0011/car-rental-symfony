<?php

namespace App\Form;

use App\Entity\Reservation;
use App\Entity\Car;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityRepository;

class ReservationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('startDate')
            ->add('endDate')
            ->add('car', EntityType::class, [
                'class' => Car::class,
                'choice_label' => 'model',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('c');
                },
            ]);

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reservation::class,
        ]);
    }
}
