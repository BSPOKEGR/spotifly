"""
Django management command to sync existing FarmParcels to the PD service
"""
from django.core.management.base import BaseCommand
from farm_management.models import FarmParcel
from farm_calendar.utils.pd_service import sync_parcel_to_pd


class Command(BaseCommand):
    help = 'Sync existing FarmParcels to the Pest & Disease (PD) service'

    def add_arguments(self, parser):
        parser.add_argument(
            '--force',
            action='store_true',
            help='Re-sync parcels that already have a pd_parcel_id',
        )
        parser.add_argument(
            '--parcel-id',
            type=str,
            help='Sync only a specific parcel by UUID',
        )

    def handle(self, *args, **options):
        force = options['force']
        parcel_id = options.get('parcel_id')

        if parcel_id:
            # Sync specific parcel
            try:
                parcel = FarmParcel.objects.get(id=parcel_id)
                parcels = [parcel]
            except FarmParcel.DoesNotExist:
                self.stdout.write(
                    self.style.ERROR(f'FarmParcel with ID {parcel_id} not found')
                )
                return
        else:
            # Sync all parcels
            if force:
                parcels = FarmParcel.objects.all()
            else:
                parcels = FarmParcel.objects.filter(pd_parcel_id__isnull=True)

        total = parcels.count()
        self.stdout.write(f'Found {total} parcels to sync')

        synced = 0
        skipped = 0
        failed = 0

        for parcel in parcels:
            # Skip if no coordinates
            if not parcel.latitude or not parcel.longitude:
                self.stdout.write(
                    self.style.WARNING(
                        f'Skipping parcel {parcel.id} ({parcel.identifier}): No coordinates'
                    )
                )
                skipped += 1
                continue

            # Skip if already synced and not forcing
            if parcel.pd_parcel_id and not force:
                self.stdout.write(
                    self.style.WARNING(
                        f'Skipping parcel {parcel.id} ({parcel.identifier}): Already synced (use --force to re-sync)'
                    )
                )
                skipped += 1
                continue

            self.stdout.write(f'Syncing parcel {parcel.id} ({parcel.identifier})...')

            try:
                pd_parcel_id = sync_parcel_to_pd(parcel)
                if pd_parcel_id:
                    parcel.pd_parcel_id = pd_parcel_id
                    parcel.save(update_fields=['pd_parcel_id'])
                    self.stdout.write(
                        self.style.SUCCESS(
                            f'  ✓ Synced to PD with ID {pd_parcel_id}'
                        )
                    )
                    synced += 1
                else:
                    self.stdout.write(
                        self.style.ERROR(
                            f'  ✗ Failed to sync (no ID returned)'
                        )
                    )
                    failed += 1
            except Exception as e:
                self.stdout.write(
                    self.style.ERROR(
                        f'  ✗ Error: {e}'
                    )
                )
                failed += 1

        self.stdout.write('')
        self.stdout.write(self.style.SUCCESS(f'Sync complete:'))
        self.stdout.write(f'  Synced: {synced}')
        self.stdout.write(f'  Skipped: {skipped}')
        self.stdout.write(f'  Failed: {failed}')
