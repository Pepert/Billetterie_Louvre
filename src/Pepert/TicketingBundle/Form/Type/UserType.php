<?php
namespace Pepert\TicketingBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $today = new \DateTime();
        $today->setTimezone(new \DateTimeZone('Europe/Paris'));
        $year = (int)$today->format('Y');

        $builder
            ->add('email',EmailType::class)
            ->add('visit_day', DateType::class, array(
                'years' => range($year,$year+1),
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