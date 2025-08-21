<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    //    /**
    //     * @return Product[] Returns an array of Product objects
    //     */
    public function SearchEngine(string $query) {
        // Crée un objet de requête qui permet de construire la requête de recherche.
        return $this->createQueryBuilder('p')
        // Recherche les éléments dont le nom contient la requête de recherche.
        ->where('p.name LIKE :query')
        // OU recherche les élées dont la description contient la requête de recherche.
        ->orWhere('p.Description LIKE :query')
        // Défini la valeur de la variable "query" pour la requête.
        ->setParameter('query', '%' . $query . '%')
        // Exécute la requête et récupère les résultats.
        ->getQuery()
        ->getResult();

    }

    //    public function findOneBySomeField($value): ?Product
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
