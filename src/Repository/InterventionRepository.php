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
        $sql = "
            SELECT 
                TO_CHAR(debut, 'FMMonth') AS month,
                COUNT(*) FILTER (WHERE ti.nom = 'Maintenance') AS maintenance,
                COUNT(*) FILTER (WHERE ti.nom = 'Réparation') AS reparation
            FROM intervention i
            JOIN type_intervention ti ON i.type_intervention_id = ti.id
            WHERE debut >= DATE_TRUNC('month', NOW() - INTERVAL '11 months') 
            AND debut < DATE_TRUNC('month', NOW() + INTERVAL '1 month') 
            AND client_id IS NOT NULL
            AND NOT (EXTRACT(MONTH FROM debut) = EXTRACT(MONTH FROM NOW()) 
                    AND EXTRACT(YEAR FROM debut) = EXTRACT(YEAR FROM NOW()) - 1)
            GROUP BY month, DATE_TRUNC('month', debut)
            ORDER BY DATE_TRUNC('month', debut);
            ";

        $resultSet = $conn->executeQuery($sql);
        return $resultSet->fetchAllAssociative();
    }

    public function getNextInterventions(int $limit = 10): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            SELECT 
                i.id AS intervention_id,
                t.nom AS type_intervention,
                u.first_name AS technicien_prenom,
                u.last_name AS technicien_nom,
                i.debut,
                i.fin,
                i.adresse
            FROM intervention i
            JOIN \"user\" u ON i.technicien_id = u.id
            JOIN type_intervention t ON i.type_intervention_id = t.id
            WHERE i.client_id IS NOT NULL
            ORDER BY i.debut ASC
            LIMIT $limit;
        ";
    
        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery();
        return $result->fetchAllAssociative();
    }
    
    
    
}
