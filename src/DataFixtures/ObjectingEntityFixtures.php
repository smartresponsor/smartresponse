<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Category\CategoryEntity;
use App\Entity\Featured\FeaturedEntity;
use App\Entity\Menu\MenuEntity;
use App\Entity\Module\ModuleEntity;
use App\Entity\Product\ProductTypeEntity;
use App\Entity\Project\ProjectTypeEntity;
use App\Entity\Review\ReviewEntity;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class ObjectingEntityFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $module = new ModuleEntity('catalog', 'catalog', 'Catalog', 'Primary catalog module', true, 'default');
        $manager->persist($module);

        $category = new CategoryEntity($module, 'catalog-category', 'catalog-category', 'Catalog category', 'Default category', null, true, 'default');
        $manager->persist($category);

        $featured = new FeaturedEntity('catalog-featured', 'catalog-featured', 'Featured catalog item', $module, true, true, 'default');
        $manager->persist($featured);

        $menu = new MenuEntity($module, 'catalog-menu', 'catalog-menu', 'Catalog menu', '/catalog', null, 0, true, 'default');
        $manager->persist($menu);

        $productType = new ProductTypeEntity($module, 'product-type', 'product-type', 'Product type', true, 'default');
        $manager->persist($productType);

        $projectType = new ProjectTypeEntity($module, 'project-type', 'project-type', 'Project type', true, 'default');
        $manager->persist($projectType);

        $review = new ReviewEntity(
            'default',
            'catalog-review',
            'module',
            'catalog',
            'fixture-author',
            5,
            'Catalog review',
            'Seeded review comment',
            null,
            'published',
            true,
            'catalog',
            ['source' => 'fixture'],
            ['seeded' => true]
        );
        $manager->persist($review);

        $manager->flush();
    }
}
