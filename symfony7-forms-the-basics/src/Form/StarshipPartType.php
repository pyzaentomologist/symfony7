<?php

namespace App\Form;

use App\Entity\Starship;
use App\Entity\StarshipPart;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Doctrine\Common\Collections\Order;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class StarshipPartType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('price', null, [
                'help' => 'We don\'t allow free parts! Set up a price',
            ])
            ->add('notes')
            ->add('starship', EntityType::class, [
                'class' => Starship::class,
                'choice_label' => function (Starship $starship) {
                    return sprintf(
                        '%s (by %s)',
                        $starship->getName(),
                        $starship->getCaptain(),
                    );
                },
                'query_builder' => function (EntityRepository $repo) {
                    return $repo->createQueryBuilder('starship')
                        ->orderBy('starship.name', Order::Ascending->value);
                },
            ])
            ->add('createAndAddNew', SubmitType::class, [
                'validate' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => StarshipPart::class,
        ]);
    }
}
