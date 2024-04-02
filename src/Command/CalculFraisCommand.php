<?php

declare(strict_types=1);

namespace App\Command;

use Google\Client;
use Google\Service\Calendar;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CalculFraisCommand extends Command
{
    private const CALENDAR_ID = 'c_ps56u1m0hb5qc46736vt2eaqkc@group.calendar.google.com';

    private const SALLES = [
        'Mambocha' => ['pricePerHour' => 6, 'pricePerEvent' => 0],
        'Art Danse' => ['pricePerHour' => 20, 'pricePerEvent' => 0],
        'Centre Social La Provence' => ['pricePerHour' => 10, 'pricePerEvent' => 0],
        'Z5' => ['pricePerHour' => 0, 'pricePerEvent' => 0],
        'Rock Caliente' => ['pricePerHour' => 0, 'pricePerEvent' => 0],
    ];

    private const MEMBRES_PAYANTS = 6;

    private const MONTHS = [
        1 => 'Janvier',
        2 => 'Février',
        3 => 'Mars',
        4 => 'Avril',
        5 => 'Mai',
        6 => 'Juin',
        7 => 'Juillet',
        8 => 'Août',
        9 => 'Septembre',
        10 => 'Octobre',
        11 => 'Novembre',
        12 => 'Décembre',
    ];

    private Client $client;

    private Calendar $calendarService;

    public function __construct(Client $client)
    {
        $this->client = $client;
        $this->client->addScope(Calendar::CALENDAR_EVENTS_READONLY);
        $this->calendarService = new Calendar($this->client);
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('calcul:frais')
            ->addOption('previous-month', 'pm', InputOption::VALUE_NONE, 'Indique si on veut les informations du mois précédent')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $month = true === $input->getOption('previous-month') ? 'previous month' : 'this month';
        $timeMin = new \DateTime('first day of '.$month.' midnight');
        $timeMax = new \DateTime('last day of '.$month.' midnight + 24 hours');

        $io->info('Récupération des répétitions à partir du '.$timeMin->format('d/m/Y').' et avant le '.$timeMax->format('d/m/Y'));

        $events = $this->calendarService->events->listEvents(self::CALENDAR_ID, [
            'singleEvents' => true,
            'q' => 'Répétition',
            'orderBy' => 'startTime',
            'timeMin' => $timeMin->format(\DateTime::RFC3339),
            'timeMax' => $timeMax->format(\DateTime::RFC3339),
        ]);

        $rowEvents = [];
        foreach (self::SALLES as $salle => $salleData) {
            $rowEvents[$salle] = array_merge($salleData, ['events' => [], 'totalHours' => 0, 'totalPrice' => 0]);
        }
        $unknownEvents = [];
        foreach ($events->getItems() as $event) {
            if (false === $event->getSummary()) {
                continue;
            }
            $startDateTime = $event->getStart() ? \DateTime::createFromFormat(\DateTime::RFC3339, $event->getStart()->getDateTime()) : null;
            $endDateTime = $event->getEnd() ? \DateTime::createFromFormat(\DateTime::RFC3339, $event->getEnd()->getDateTime()) : null;
            $location = $event->getLocation() ? preg_split("/,|\r\n|\n|\r/", $event->getLocation()) : null;
            /** @var string $location */
            $location = \is_array($location) ? reset($location) : '-';
            $duration = $startDateTime instanceof \DateTime && $endDateTime instanceof \DateTime ? $endDateTime->diff($startDateTime) : null;
            $duration = null !== $duration ? (int) $duration->format('%h') + ((int) $duration->format('%i')) / 60 : null;
            $rowEvent = [
                $event->getSummary(),
                $startDateTime instanceof \DateTime ? $startDateTime->format('l d') : '-',
                $startDateTime instanceof \DateTime ? $startDateTime->format('H:i') : '-',
                $endDateTime instanceof \DateTime ? $endDateTime->format('H:i') : '-',
                $duration ?? '-',
            ];

            if (1 === preg_match('/mambocha/i', $event->getSummary()) || 1 === preg_match('/mambocha/i', $location)) {
                $salle = 'Mambocha';
                $prixSalle = self::SALLES[$salle];
                $rowEvents[$salle]['events'][] = $rowEvent;
                $rowEvents[$salle]['totalHours'] += $duration;
                $rowEvents[$salle]['totalPrice'] += $prixSalle['pricePerEvent'] + $duration * $prixSalle['pricePerHour'];

                continue;
            }

            if (1 === preg_match('/art danse/i', $event->getSummary()) || 1 === preg_match('/art danse/i', $location) || 1 === preg_match('/grand rue/i', $location)) {
                $salle = 'Art Danse';
                $prixSalle = self::SALLES[$salle];
                $rowEvents[$salle]['events'][] = $rowEvent;
                $rowEvents[$salle]['totalHours'] += $duration;
                $rowEvents[$salle]['totalPrice'] += $prixSalle['pricePerEvent'] + $duration * $prixSalle['pricePerHour'];

                continue;
            }

            if (1 === preg_match('/centre social/i', $event->getSummary()) || 1 === preg_match('/centre social/i', $location)) {
                $salle = 'Centre Social La Provence';
                $prixSalle = self::SALLES[$salle];
                $rowEvents[$salle]['events'][] = $rowEvent;
                $rowEvents[$salle]['totalHours'] += $duration;
                $rowEvents[$salle]['totalPrice'] += $prixSalle['pricePerEvent'] + $duration * $prixSalle['pricePerHour'];

                continue;
            }

            if (1 === preg_match('/Z5/i', $event->getSummary()) || 1 === preg_match('/Z5/i', $location)) {
                $salle = 'Z5';
                $prixSalle = self::SALLES[$salle];
                $rowEvents[$salle]['events'][] = $rowEvent;
                $rowEvents[$salle]['totalHours'] += $duration;
                $rowEvents[$salle]['totalPrice'] += $prixSalle['pricePerEvent'] + $duration * $prixSalle['pricePerHour'];

                continue;
            }

            if (1 === preg_match('/Rock Caliente/i', $event->getSummary()) || 1 === preg_match('/Rock Caliente/i', $location)) {
                $salle = 'Rock Caliente';
                $prixSalle = self::SALLES[$salle];
                $rowEvents[$salle]['events'][] = $rowEvent;
                $rowEvents[$salle]['totalHours'] += $duration;
                $rowEvents[$salle]['totalPrice'] += $prixSalle['pricePerEvent'] + $duration * $prixSalle['pricePerHour'];

                continue;
            }

            $unknownEvents[] = array_merge([$location], $rowEvent);
        }

        $totalPrices = [];
        $total = 0;
        $whatsappMessage = ['Frais salles '.self::MONTHS[(int) $timeMin->format('m')].' :'];
        foreach ($rowEvents as $place => $rowEventPlace) {
            $tarif = $rowEventPlace['pricePerHour'] > 0 ? $rowEventPlace['pricePerHour'].'€/h' : ($rowEventPlace['pricePerEvent'] > 0 ? $rowEventPlace['pricePerEvent'].'€/séance' : 'offert');
            $io->title('Répétitions à '.$place.' ('.$tarif.')');
            $whatsappMessage[] = '';
            $whatsappMessage[] = '*'.$place.' ('.$tarif.')*';
            $whatsappMessage[] = \count($rowEventPlace['events']).' répétitions pour un total de '.$rowEventPlace['totalHours'].'h';
            $whatsappMessage[] = 'Tarif : '.$rowEventPlace['totalPrice'].'€';
            if (0 === \count($rowEventPlace['events'])) {
                $io->text('Aucune répétition pendant le mois');

                continue;
            }
            $io->table(
                ['Titre', 'Jour', 'Début', 'Fin', 'Durée'],
                $rowEventPlace['events']
            );
            $io->text('Tarif pour '.\count($rowEventPlace['events']).' répétitions et un total de '.$rowEventPlace['totalHours'].'h : '.$rowEventPlace['totalPrice'].'€');
            if ($rowEventPlace['totalPrice'] > 0) {
                $totalPrices[] = $rowEventPlace['totalPrice'];
                $total += $rowEventPlace['totalPrice'];
            }
        }

        if (\count($unknownEvents) > 0) {
            $io->error('Liste des répétitions indéterminées');
            $io->table(
                ['Lieu', 'Titre', 'Jour', 'Début', 'Fin', 'Durée'],
                $unknownEvents
            );
        }

        $totalPerPerson = ceil(round($total / self::MEMBRES_PAYANTS * 100)) / 100;

        $io->success(['Total : '.implode('€ + ', $totalPrices).'€ = '.$total.'€', 'Par pers : '.$total.'€ / '.self::MEMBRES_PAYANTS.' = '.$totalPerPerson.'€']);

        $whatsappMessage[] = '';
        $whatsappMessage[] = '*Total*';
        $whatsappMessage[] = 'Total : '.implode('€ + ', $totalPrices).'€ = '.$total.'€';
        $whatsappMessage[] = 'Par pers : '.$total.'€ / '.self::MEMBRES_PAYANTS.' = *'.$totalPerPerson.'€*';

        $io->title('Message à envoyer sur WhatsApp');
        $io->writeln($whatsappMessage);

        return Command::SUCCESS;
    }
}
