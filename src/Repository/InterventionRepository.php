<?php

namespace App\Repository;

use App\Entity\Intervention;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Intervention>
 */
class InterventionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Intervention::class);
    }

    /**
     * Retourne les interventions d’un technicien, avec des options de filtrage.
     *
     * @param int $technicienId ID du technicien concerné.
     * @param bool|null $reservedOnly Si true, retourne uniquement les interventions réservées (ayant un client).
     *                                Si false ou null, retourne toutes les interventions.
     * @param \DateTimeInterface|null $date Si spécifiée, filtre les interventions dont la date de début est le jour donné (entre 00:00 et 23:59:59).
     *
     * @return Intervention[] Liste des interventions correspondant aux critères.
     */
    public function findByTechnicienWithFilter(int $technicienId, ?bool $reservedOnly = false, ?\DateTimeInterface $date = null): array
    {
        $qb = $this->createQueryBuilder('i')
            ->where('i.technicien = :technicienId')
            ->setParameter('technicienId', $technicienId)
            ->orderBy('i.debut', 'ASC');

        if ($reservedOnly === true) {
            $qb->andWhere('i.client IS NOT NULL');
        }

        if ($date) {
            $start = (clone $date)->setTime(0, 0, 0);
            $end = (clone $date)->setTime(23, 59, 59);
            $qb->andWhere('i.debut BETWEEN :start AND :end')
                ->setParameter('start', $start)
                ->setParameter('end', $end);
        }

        return $qb->getQuery()->getResult();
    }


    public function findNonReservedInterventionsByTechnicianAndDateRange(
        \App\Entity\User $technician,
        \DateTime $from,
        \DateTime $to
    ): array {
        return $this->createQueryBuilder('i')
            ->where('i.technicien = :technician')
            ->andWhere('i.client IS NULL') // Interventions non réservées
            ->andWhere('i.debut BETWEEN :from AND :to')
            ->setParameter('technician', $technician)
            ->setParameter('from', $from->format('Y-m-d 00:00:00'))
            ->setParameter('to', $to->format('Y-m-d 23:59:59'))
            ->getQuery()
            ->getResult();
    }

public function interventionsByTypeLastTwelveMonths(): array
{
    $conn = $this->getEntityManager()->getConnection();
    $sql = <<<SQL
        WITH months AS (
            SELECT date_trunc('month', (now() - interval '11 months')::date) + (gs.i * interval '1 month') AS month_start
            FROM generate_series(0,11) AS gs(i)
        ),
        agg AS (
            SELECT
            date_trunc('month', i.debut) AS month_start,
            SUM(CASE WHEN ti.nom = 'Maintenance' THEN 1 ELSE 0 END) AS maintenance,
            SUM(CASE WHEN ti.nom = 'Réparation' THEN 1 ELSE 0 END) AS reparation
            FROM intervention i
            JOIN type_intervention ti ON ti.id = i.type_intervention_id
            WHERE i.debut >= date_trunc('month', now() - interval '11 months')
            AND i.debut <  date_trunc('month', now() + interval '1 month')
            AND i.client_id IS NOT NULL
            GROUP BY date_trunc('month', i.debut)
        )
        SELECT
        to_char(m.month_start, 'FMMonth') AS month,
        COALESCE(a.maintenance, 0) AS maintenance,
        COALESCE(a.reparation, 0)  AS reparation
        FROM months m
        LEFT JOIN agg a ON a.month_start = m.month_start
        ORDER BY m.month_start;
        SQL;
    return $conn->executeQuery($sql)->fetchAllAssociative();
}

    public function getNextInterventions(int $limit = 10): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $limit = max(1, min(100, $limit));
        $sql = <<<SQL
            SELECT 
                i.id AS intervention_id,
                t.nom AS type_intervention,
                u.first_name AS technicien_prenom,
                u.last_name AS technicien_nom,
                i.debut,
                i.fin,
                i.adresse
            FROM intervention i
            JOIN 'user' u ON i.technicien_id = u.id
            JOIN type_intervention t ON i.type_intervention_id = t.id
            WHERE i.client_id IS NOT NULL
            ORDER BY i.debut ASC
            LIMIT :limit
        SQL;
    
        $stmt = $conn->prepare($sql);
        $stmt->bindValue('limit', $limit, \PDO::PARAM_INT);
        $result = $stmt->executeQuery();
        return $result->fetchAllAssociative();
    }
}
