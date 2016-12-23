<?php

namespace Oro\Bundle\NavigationBundle\Menu;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\NavigationBundle\Event\ConfigureMenuEvent;
use Oro\Bundle\NavigationBundle\Provider\ConfigurationProvider;

use Oro\Component\Config\Resolver\ResolverInterface;

class ConfigurationBuilder implements BuilderInterface
{
    const DEFAULT_SCOPE_TYPE = 'menu_default_visibility';

    /** @var ResolverInterface */
    protected $resolver;

    /** @var EventDispatcherInterface */
    private $factory;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /** @var ConfigurationProvider */
    private $configurationProvider;

    /**
     * @param ResolverInterface        $resolver
     * @param FactoryInterface         $factory
     * @param EventDispatcherInterface $eventDispatcher
     * @param ConfigurationProvider    $configurationProvider
     */
    public function __construct(
        ResolverInterface $resolver,
        FactoryInterface $factory,
        EventDispatcherInterface $eventDispatcher,
        ConfigurationProvider $configurationProvider
    ) {
        $this->resolver = $resolver;
        $this->factory = $factory;
        $this->eventDispatcher = $eventDispatcher;
        $this->configurationProvider = $configurationProvider;
    }

    /**
     * Modify menu by adding, removing or editing items.
     *
     * @param ItemInterface $menu
     * @param array         $options
     * @param string|null   $alias
     */
    public function build(ItemInterface $menu, array $options = [], $alias = null)
    {
        // TODO set const
        $menuConfig = $this->configurationProvider->getConfiguration('oro_menu_config');

        if (!empty($menuConfig['items']) && !empty($menuConfig['tree'])) {
            foreach ($menuConfig['tree'] as $menuTreeName => $menuTreeElement) {
                if ($menuTreeName == $alias) {
                    if (!empty($menuTreeElement['extras'])) {
                        $menu->setExtras($menuTreeElement['extras']);
                    }

                    $defaultArea = ConfigurationBuilder::DEFAULT_SCOPE_TYPE;
                    $this->setExtraFromConfig($menu, $menuTreeElement, 'type');
                    $this->setExtraFromConfig($menu, $menuTreeElement, 'scope_type', $defaultArea);
                    $this->setExtraFromConfig($menu, $menuTreeElement, 'read_only', false);
                    $this->setExtraFromConfig($menu, $menuTreeElement, 'max_nesting_level', 0);

                    $this->createFromArray($menu, $menuTreeElement['children'], $menuConfig['items'], $options);
                }
            }
        }

        $event = new ConfigureMenuEvent($this->factory, $menu);
        $this->eventDispatcher->dispatch(ConfigureMenuEvent::getEventName($alias), $event);
    }

    /**
     * @param ItemInterface $menu
     * @param array         $config
     * @param string        $optionName
     * @param mixed         $default
     */
    private function setExtraFromConfig($menu, $config, $optionName, $default = null)
    {
        if (!empty($config[$optionName])) {
            $menu->setExtra($optionName, $config[$optionName]);
        } elseif ($default !== null) {
            $menu->setExtra($optionName, $default);
        }
    }

    /**
     * @param ItemInterface $menu
     * @param array         $data
     * @param array         $itemList
     * @param array         $options
     * @param array         $itemCodes
     */
    private function createFromArray(
        ItemInterface $menu,
        array $data,
        array &$itemList,
        array $options = [],
        array &$itemCodes = []
    ) {
        $isAllowed = false;
        foreach ($data as $itemCode => $itemData) {
            if (in_array($itemCode, $itemCodes)) {
                throw new \InvalidArgumentException(sprintf(
                    'Item key "%s" duplicated in tree menu "%s".',
                    $itemCode,
                    $menu->getRoot()->getName()
                ));
            }
            $itemCodes[] = $itemCode;

            $itemData = $this->resolver->resolve($itemData);
            if (!empty($itemList[$itemCode])) {
                $itemOptions = $itemList[$itemCode];

                if (empty($itemOptions['name'])) {
                    $itemOptions['name'] = $itemCode;
                }

                if (!empty($itemData['position'])) {
                    $itemOptions['extras']['position'] = $itemData['position'];
                }

                $this->moveToExtras($itemOptions, 'translateDomain');
                $this->moveToExtras($itemOptions, 'translateParameters');
                $this->moveToExtras($itemOptions, 'translate_disabled');
                $this->moveToExtras($itemOptions, 'acl_resource_id');

                $newMenuItem = $menu->addChild($itemOptions['name'], array_merge($itemOptions, $options));

                if (!empty($itemData['children'])) {
                    $this->createFromArray($newMenuItem, $itemData['children'], $itemList, $options, $itemCodes);
                }

                $isAllowed = $isAllowed || $newMenuItem->getExtra('isAllowed');
            }
        }

        if ($menu->getExtra('isAllowed')) {
            $menu->setExtra('isAllowed', $isAllowed);
        }
    }

    /**
     * @param array  $menuItem
     * @param string $optionName
     *
     * @return void
     */
    private function moveToExtras(array &$menuItem, $optionName)
    {
        if (isset($menuItem[$optionName])) {
            $menuItem['extras'][$optionName] = $menuItem[$optionName];
            unset($menuItem[$optionName]);
        }
    }
}
