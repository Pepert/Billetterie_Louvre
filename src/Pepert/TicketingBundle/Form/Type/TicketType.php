<?php
namespace Pepert\TicketingBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class TicketType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $today = new \DateTime();
        $today->setTimezone(new \DateTimeZone('Europe/Paris'));
        $year = (int)$today->format('Y');

        $builder
            ->add('name',TextType::class)
            ->add('firstname',TextType::class)
            ->add('country',CountryType::class)
            ->add('birthday',BirthdayType::class, array('years' => range(1902,$year)))
            ->add('tarif_reduit',CheckboxType::class, array('required' => false))
            ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Pepert\TicketingBundle\Entity\Ticket',
        ));
    }
}