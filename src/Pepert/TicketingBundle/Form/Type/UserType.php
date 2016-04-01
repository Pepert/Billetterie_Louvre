<?php
namespace Pepert\TicketingBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name',TextType::class)
            ->add('firstname',TextType::class)
            ->add('email',EmailType::class)
            ->add('visit_day', DateType::class, array(
                'years' => range(2016,2018),
                'format' => 'dd MMMM yyyy',
            ))
            ->add('ticket_type', ChoiceType::class, array(
                'choices'  => array(
                    'Journée' => 'Journée',
                    'Demi-journée' => 'Demi-journée',
                )
            ))
            ->add('ticket_number',IntegerType::class, array(
                'attr' => array(
                    'min' => 1,
                    'max' => 25
                )
            ))
            ->add('submit',SubmitType::class)
            ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Pepert\TicketingBundle\Entity\User',
        ));
    }
}