<?php

namespace App\Services;

use App\Models\Alert;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AlertService
{
    const string LOW_RISK = 'LOW';
    const string MEDIUM_RISK = 'MEDIUM';
    const string HIGH_RISK = 'HIGH';
    const string CRITICAL_RISK = 'CRITICAL';

    public function getAlertsForParcels(array $parcels): array
    {
        $parcelIds = array_keys($parcels);

        $alerts = Alert::whereIn('parcel_id', $parcelIds)
            ->where('read', false)
            ->orderBy('date', 'desc')
            ->get();

        // If no unread alerts, fall back to the single latest read alert
        if ($alerts->isEmpty()) {
            $alerts = Alert::whereIn('parcel_id', $parcelIds)
                ->where('read', true)
                ->orderBy('date', 'desc')
                ->limit(1)
                ->get();
        }

        $responseAlerts = [];
        foreach ($alerts as $alert) {
            $responseAlerts[] = [
                'id' => $alert['id'],
                'parcel_id' => $alert['parcel_id'],
                'parcel_name' => $parcels[$alert['parcel_id']] ?? 'Unknown Parcel',
                'date' => $alert['date'],
                'type' => $alert['type'],
                'severity' => $alert['severity'],
                'message' => $alert['message'],
                'read' => $alert['read'],
            ];
        }

        return $responseAlerts;
    }

    public function saveAlertsForParcel(string $parcelId, array $alerts): void
    {
        foreach ($alerts as $pest => $alert) {
            if (isset($alert['current_risk_level']) && self::LOW_RISK === $alert['current_risk_level']) {
                continue; // Skip low risk alerts
            }
            $message = $this->generateAlertMessage($pest, $alert['current_risk_level']);

            Alert::create([
                'parcel_id' => $parcelId,
                'date' => $alert['to_date'],
                'type' => $pest,
                'severity' => $alert['current_risk_level'],
                'message' => $message,
                'data' => $alert ?? null,
            ]);
        }
    }

    private function generateAlertMessage(string $pest, mixed $riskLevel): string
    {
        switch ($pest) {
            case PestDiseaseService::OLIVE_MOTH:
                $pest = 'Olive Moth';
                break;
            case PestDiseaseService::OLIVE_FRUIT_FLY:
                $pest = 'Olive Fruit Fly';
                break;
            default:
                $pest = ucfirst(str_replace('_', ' ', $pest));
        }

        switch ($riskLevel) {
            case self::LOW_RISK:
                $riskLevel = 'Low';
                break;
            case self::MEDIUM_RISK:
                $riskLevel = 'Medium';
                break;
            case self::HIGH_RISK:
                $riskLevel = 'High';
                break;
            case self::CRITICAL_RISK:
                $riskLevel = 'Critical';
                break;
            default:
                $riskLevel = ucfirst(strtolower($riskLevel));
        }

        return "Alert for $pest: Current risk level is $riskLevel.";
    }

    public function getAlertTimelineForParcels(array $parcels): array
    {
        $parcelIds = array_keys($parcels);
        $today = now()->startOfDay();

        $alerts = Alert::whereIn('parcel_id', $parcelIds)
            ->whereBetween('date', [$today->copy()->subDays(9), $today->copy()->endOfDay()])
            ->orderBy('date', 'desc')
            ->get();

        // Group alerts by date string
        $byDate = [];
        foreach ($alerts as $alert) {
            $byDate[$alert->date->toDateString()][] = [
                'id' => $alert->id,
                'parcel_id' => $alert->parcel_id,
                'parcel_name' => $parcels[$alert->parcel_id] ?? 'Unknown Parcel',
                'type' => $alert->type,
                'severity' => $alert->severity,
                'message' => $alert->message,
                'read' => $alert->read,
            ];
        }

        $timeline = [];
        for ($i = 0; $i < 10; $i++) {
            $date = $today->copy()->subDays($i)->toDateString();
            if (isset($byDate[$date])) {
                $timeline[] = ['date' => $date, 'alerts' => $byDate[$date]];
            } else {
                $timeline[] = ['date' => $date, 'alerts' => null, 'message' => 'No alerts for this day'];
            }
        }

        return array_reverse($timeline);
    }

    public function acknowledgeAlert(string $id)
    {
        $alert = Alert::find($id);
        if ($alert) {
            $alert->read = true;
            $alert->save();
        } else {
            throw new NotFoundHttpException("Alert with ID $id not found.");
        }
    }
}
