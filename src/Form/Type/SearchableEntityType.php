<?php

declare(strict_types=1);

namespace App\Form\Type;

use App\Controller\CP\SearchAPIInterface;
use App\Entity\SearchableEntityInterface;
use LogicException;
use Override;
use ReflectionClass;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Attribute\Route;

class SearchableEntityType extends AbstractType
{
    public function __construct()
    {
    }

    #[Override]
    public function getParent(): string
    {
        return EntityType::class;
    }

    /**
     * {@inheritdoc}
     * @psalm-suppress PropertyTypeCoercion
     */
    #[Override]
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {

        /** @var class-string<SearchableEntityInterface> $className */
        $className = $options['class'];
        $searchAPIControllerClass = $className::getSearchAPIController();

        if (!is_a($searchAPIControllerClass, SearchAPIInterface::class, true)) {
            throw new LogicException(sprintf('%s does not implement %s', $searchAPIControllerClass, SearchAPIInterface::class));
        }

        $routeAttributes = (new ReflectionClass($searchAPIControllerClass))->getMethod('searchAPI')->getAttributes(Route::class);

        if (count($routeAttributes) !== 1) {
            throw new LogicException('No route attribute on searchAPI method.');
        }

        $view->vars['search_api_path'] = (string) ($routeAttributes[0]->getArguments())['name'];
        $view->vars['ajax_delay'] = intval($options['ajax_delay']);
        $view->vars['ajax_minimum_input_length'] = intval($options['ajax_minimum_input_length']);
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {

        $resolver->setDefaults([
            'attr' => [
                'class' => 'choices-select-xhr',
            ],
            'required' => false,
            'mapped' => true,
            'multiple' => true,
            'ajax_delay' => 250,
            'ajax_minimum_input_length' => 2,
            'choice_lazy' => true,
        ]);

        $resolver->setAllowedTypes('ajax_delay', 'int');

        $resolver->setNormalizer('class', function (Options $options, $entityClass) {

            if (null !== $entityClass && !is_a($entityClass, SearchableEntityInterface::class, true)) {
                throw new LogicException(sprintf('%s is does not implement %s', $entityClass, SearchableEntityInterface::class));
            }

            return $entityClass;
        });
    }

    #[Override]
    public function getBlockPrefix(): string
    {
        return 'searchable_entity';
    }
}
