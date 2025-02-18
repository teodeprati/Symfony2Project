<?php

namespace App\Repository;

use App\Entity\Categorie;
use App\Entity\Article;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Article>
 */
class ArticleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Article::class);
    }

       /**
        * @return Article[] Returns an array of Article objects
        */
        public function findByCategory(Categorie $category)
        {
            return $this->createQueryBuilder('a')
                ->innerJoin('a.categories', 'c') // Liaison avec la relation "categories"
                ->andWhere('c = :category') // Filtrage par la catégorie
                ->setParameter('category', $category)
                ->getQuery()
                ->getResult();
        }

    public function findBySearch(string $search = null)
    {
        $qb = $this->createQueryBuilder('a')
            ->orderBy('a.createdAt', 'DESC');

        if ($search) {
            $qb->andWhere('LOWER(a.titre) LIKE LOWER(:search) OR LOWER(a.content) LIKE LOWER(:search)')
               ->setParameter('search', '%' . $search . '%');
        }

        return $qb->getQuery()->getResult();
    }
}
