<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Form\ReservationType;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

#[Route('/reservation')]
final class ReservationController extends AbstractController
{
    #[Route(name: 'app_reservation_index', methods: ['GET'])]
    public function index(
        ReservationRepository $reservationRepository
    ): Response {

        return $this->render('reservation/index.html.twig', [
            'reservations' => $reservationRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_reservation_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        ReservationRepository $reservationRepository,
        SluggerInterface $slugger
    ): Response {

        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $reservation = new Reservation();

        $form = $this->createForm(
            ReservationType::class,
            $reservation
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $reservation->setUser($this->getUser());

            $start = $reservation->getStartDate();
            $end   = $reservation->getEndDate();
            $car   = $reservation->getCar();

            if (!$start || !$end || !$car) {

                $this->addFlash(
                    'error',
                    'Please fill all fields correctly.'
                );

                return $this->redirectToRoute(
                    'app_reservation_new'
                );
            }

            if ($start > $end) {

                $this->addFlash(
                    'error',
                    'Start date must be before end date.'
                );

                return $this->redirectToRoute(
                    'app_reservation_new'
                );
            }

            $licenseDate = $reservation->getLicenseIssueDate();

            if ($licenseDate) {

                $today = new \DateTime();

                $difference = $today->diff($licenseDate);

                if ($difference->y < 2) {

                    $this->addFlash(
                        'error',
                        'Driver license must be older than 2 years.'
                    );

                    return $this->redirectToRoute(
                        'app_reservation_new'
                    );
                }
            }

            if (
                $reservationRepository->hasOverlap(
                    $car,
                    $start,
                    $end
                )
            ) {

                $this->addFlash(
                    'error',
                    'This car is already reserved for these dates.'
                );

                return $this->redirectToRoute(
                    'app_reservation_new'
                );
            }


            // CIN IMAGE


            $cinFile = $form->get('cinImage')->getData();

            if ($cinFile) {

                $originalFilename = pathinfo(
                    $cinFile->getClientOriginalName(),
                    PATHINFO_FILENAME
                );

                $safeFilename = $slugger->slug(
                    $originalFilename
                );

                $newFilename =
                    $safeFilename
                    .'-'
                    .uniqid()
                    .'.'
                    .$cinFile->guessExtension();

                try {

                    $cinFile->move(
                        $this->getParameter('kernel.project_dir')
                        .'/public/uploads/cin',
                        $newFilename
                    );

                } catch (FileException $e) {

                }

                $reservation->setCinImage($newFilename);
            }


            // LICENSE IMAGE


            $licenseFile = $form->get('licenseImage')->getData();

            if ($licenseFile) {

                $originalFilename = pathinfo(
                    $licenseFile->getClientOriginalName(),
                    PATHINFO_FILENAME
                );

                $safeFilename = $slugger->slug(
                    $originalFilename
                );

                $newFilename =
                    $safeFilename
                    .'-'
                    .uniqid()
                    .'.'
                    .$licenseFile->guessExtension();

                try {

                    $licenseFile->move(
                        $this->getParameter('kernel.project_dir')
                        .'/public/uploads/licenses',
                        $newFilename
                    );

                } catch (FileException $e) {

                }

                $reservation->setLicenseImage($newFilename);
            }


            // TOTAL PRICE


            $days = max(
                1,
                $start->diff($end)->days
            );

            $reservation->setTotalPrice(
                $days * $car->getPricePerDay()
            );

            $reservation->setStatus('pending');

            $entityManager->persist($reservation);

            $entityManager->flush();

            $this->addFlash(
                'success',
                'Reservation created successfully.'
            );

            return $this->redirectToRoute(
                'app_reservation_index'
            );
        }

        return $this->render(
            'reservation/new.html.twig',
            [
                'reservation' => $reservation,
                'form' => $form,
            ]
        );
    }


    // CALENDAR ROUTE



    #[Route('/calendar', name: 'app_reservation_calendar', methods: ['GET'])]
    public function calendar(
        ReservationRepository $reservationRepository
    ): Response {

        $reservations = $reservationRepository->findBy([
            'status' => ['pending', 'approved']
        ]);

        $events = [];

        foreach ($reservations as $reservation) {

            $events[] = [

                'title' =>
                    $reservation->getCar()->getBrand()
                    .' '
                    .$reservation->getCar()->getModel(),

                'start' =>
                    $reservation->getStartDate()->format('Y-m-d'),

                'end' =>
                    (clone $reservation->getEndDate())
                        ->modify('+1 day')
                        ->format('Y-m-d'),

                'backgroundColor' => match ($reservation->getStatus()) {

                    'approved' => '#198754',
                    'pending' => '#ffc107',
                    'rejected' => '#dc3545',

                    default => '#6c757d',
                },

                'borderColor' => match ($reservation->getStatus()) {

                    'approved' => '#198754',
                    'pending' => '#ffc107',
                    'rejected' => '#dc3545',

                    default => '#6c757d',
                },
            ];
        }

        return $this->render(
            'reservation/calendar.html.twig',
            [
                'events' => json_encode($events),
            ]
        );
    }

    #[Route('/{id}', name: 'app_reservation_show', methods: ['GET'])]
    public function show(
        Reservation $reservation
    ): Response {

        return $this->render(
            'reservation/show.html.twig',
            [
                'reservation' => $reservation,
            ]
        );
    }

    #[Route('/{id}/edit', name: 'app_reservation_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Reservation $reservation,
        EntityManagerInterface $entityManager
    ): Response {

        $form = $this->createForm(
            ReservationType::class,
            $reservation
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $entityManager->flush();

            $this->addFlash(
                'success',
                'Reservation updated successfully.'
            );

            return $this->redirectToRoute(
                'app_reservation_index'
            );
        }

        return $this->render(
            'reservation/edit.html.twig',
            [
                'reservation' => $reservation,
                'form' => $form,
            ]
        );
    }

    #[Route('/{id}', name: 'app_reservation_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Reservation $reservation,
        EntityManagerInterface $entityManager
    ): Response {

        if (
            $this->isCsrfTokenValid(
                'delete'.$reservation->getId(),
                $request->getPayload()->getString('_token')
            )
        ) {

            $entityManager->remove($reservation);

            $entityManager->flush();

            $this->addFlash(
                'success',
                'Reservation deleted successfully.'
            );
        }

        return $this->redirectToRoute(
            'app_reservation_index'
        );
    }

    #[Route('/{id}/approve', name: 'app_reservation_approve', methods: ['POST'])]
    public function approve(
        Reservation $reservation,
        EntityManagerInterface $entityManager
    ): Response {

        $reservation->setStatus('approved');

        $reservation->getCar()->setStatus('unavailable');

        $entityManager->flush();

        $this->addFlash(
            'success',
            'Reservation approved successfully.'
        );

        return $this->redirectToRoute(
            'app_reservation_show',
            ['id' => $reservation->getId()]
        );
    }

    #[Route('/{id}/reject', name: 'app_reservation_reject', methods: ['POST'])]
    public function reject(
        Reservation $reservation,
        EntityManagerInterface $entityManager
    ): Response {

        $reservation->setStatus('rejected');

        $entityManager->flush();

        $this->addFlash(
            'danger',
            'Reservation rejected.'
        );

        return $this->redirectToRoute(
            'app_reservation_show',
            ['id' => $reservation->getId()]
        );
    }
}
