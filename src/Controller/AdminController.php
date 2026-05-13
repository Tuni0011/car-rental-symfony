<?php

namespace App\Controller;

use App\Repository\CarRepository;
use App\Repository\ReservationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[Route('/dashboard', name: 'admin_dashboard')]
    public function index(
        CarRepository $carRepository,
        ReservationRepository $reservationRepository
    ): Response {

        $carsCount = $carRepository->count([]);
        $reservationsCount = $reservationRepository->count([]);

        $reservations = $reservationRepository->findBy([], ['id' => 'DESC'], 5);

        $totalRevenue = $reservationRepository->createQueryBuilder('r')
            ->select('SUM(r.totalPrice)')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        return $this->render('admin/dashboard.html.twig', [
            'carsCount' => $carsCount,
            'reservationsCount' => $reservationsCount,
            'reservations' => $reservations,
            'totalRevenue' => $totalRevenue,
        ]);
    }

    // =========================
    // CARS MANAGEMENT PAGE
    // =========================
    #[Route('/cars', name: 'admin_cars')]
    public function cars(CarRepository $carRepository, Request $request): Response
    {
        $status = $request->query->get('status');

        if ($status) {
            $cars = $carRepository->findBy(['status' => $status]);
        } else {
            $cars = $carRepository->findAll();
        }

        return $this->render('admin/cars.html.twig', [
            'cars' => $cars,
            'currentStatus' => $status,
        ]);
    }
}
