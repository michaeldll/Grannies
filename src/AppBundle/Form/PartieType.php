<?php

namespace AppBundle\Form;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PartieType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('joueur1', EntityType::class, array(
            'class' => 'AppBundle\Entity\Joueur',
            'choice_label' => 'username',
            'label' => 'Selectionnez le joueur 1'
        ))
                ->add('joueur2', EntityType::class, array(
            'class' => 'AppBundle\Entity\Joueur',
            'choice_label' => 'username',
            'label' => 'Selectionnez le joueur 2'
        ));
    }
    
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\Partie'
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'appbundle_partie';
    }


}
