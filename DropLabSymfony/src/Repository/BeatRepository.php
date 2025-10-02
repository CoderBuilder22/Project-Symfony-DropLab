<?php

namespace App\Repository;

use App\Entity\Beat;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Beat>
 *
 * @method Beat|null find($id, $lockMode = null, $lockVersion = null)
 * @method Beat|null findOneBy(array $criteria, array $orderBy = null)
 * @method Beat[]    findAll()
 * @method Beat[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BeatRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Beat::class);
    }

    public function save(Beat $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Beat $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByGenre(string $genre)
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.genre = :genre')
            ->setParameter('genre', $genre)
            ->orderBy('b.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByBpmAndPrice(?int $bpm, ?float $price, ?string $genre = null)
    {
        $qb = $this->createQueryBuilder('b');

        if ($genre) {
            $qb->andWhere('b.genre = :genre')
               ->setParameter('genre', $genre);
        }

        if ($bpm !== null) {
            $qb->andWhere('b.bpm = :bpm')
               ->setParameter('bpm', $bpm);
        }

        if ($price !== null) {
            $qb->andWhere('b.price <= :price')
               ->setParameter('price', $price);
        }

        return $qb->orderBy('b.createdAt', 'DESC')
                  ->getQuery()
                  ->getResult();
    }

    public function search(string $query)
    {
        return $this->createQueryBuilder('b')
            ->leftJoin('b.producer', 'p')
            ->where('b.title LIKE :query')
            ->orWhere('b.genre LIKE :query')
            ->orWhere('p.username LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('b.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
} 