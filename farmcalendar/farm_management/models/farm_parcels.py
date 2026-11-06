from django.db import models
from django.utils.translation import gettext_lazy as _

from .base import BaseModel, LocationBaseModel, ActivePageManager
from .validators import *


class Farm(BaseModel):
    name = models.CharField(
        _('Farm Name'), max_length=255, unique=False,
        blank=False, null=False,
        default="Unnamed Farm", validators=[]
    )
    description = models.TextField(_('Farm Description'), blank=True, null=True)
    administrator = models.CharField(_('Farm Administrator'), max_length=255, blank=True, null=True, validators=[])
    contact_person_firstname = models.CharField(
        _('Contact Person First Name'), max_length=255, blank=True, null=True, validators=[])
    contact_person_lastname = models.CharField(
        _('Contact Person Last Name'), max_length=255, blank=True, null=True, validators=[])
    telephone = models.CharField(_('Contact Telephone'), max_length=20, blank=True, null=True, validators=[])
    vat_id = models.CharField(_('VAT ID'), max_length=50, blank=True, null=True, validators=[])

    admin_unit_l1 = models.CharField(_('Admin Unit Line 1'), max_length=255, blank=True, null=True, validators=[])
    admin_unit_l2 = models.CharField(_('Admin Unit Line 2'), max_length=255, blank=True, null=True, validators=[])
    address_area = models.CharField(_('Address Area'), max_length=255, blank=True, null=True, validators=[])
    municipality = models.CharField(_('Municipality'), max_length=255, blank=True, null=True, validators=[])
    community = models.CharField(_('Community'), max_length=255, blank=True, null=True, validators=[])
    locator_name = models.CharField(_('Locator Name'), max_length=255, blank=True, null=True, validators=[])

    objects = models.Manager()
    active_objects = ActivePageManager()

    class Meta:
        verbose_name = "Farm"
        verbose_name_plural = "Farms"

    def __str__(self):
        return f"{self.name}"

class FarmParcel(BaseModel, LocationBaseModel):

    identifier = models.CharField(max_length=255, unique=True, blank=False, null=False,
                                  validators=[])
    farm = models.ForeignKey(Farm, on_delete=models.CASCADE, blank=False, null=False,
                             related_name="farm_parcels")
    description = models.TextField(blank=True, null=True)
    parcel_type = models.CharField(_('Type of Parcel'), max_length=50)

    valid_from = models.DateTimeField(blank=True, null=True, validators=[])
    valid_to = models.DateTimeField(blank=True, null=True, validators=[])

    in_region = models.CharField(max_length=255, blank=True, null=True, validators=[])
    has_toponym = models.CharField(max_length=255, blank=True, null=True, validators=[])
    area = models.DecimalField(max_digits=15, decimal_places=2, default=0.0)
    is_nitro_area = models.BooleanField(default=False)
    is_natura2000_area = models.BooleanField(default=False)
    is_pdopg_area = models.BooleanField(default=False)
    is_irrigated = models.BooleanField(default=False)
    is_cultivated_in_levels = models.BooleanField(default=False)
    is_ground_slope = models.BooleanField(default=False)
    depiction = models.URLField(max_length=500, blank=True, null=True)
    # irrigation_system = models.ForeignKey(IrrigationSystem, on_delete=models.SET_NULL, null=True, blank=True, related_name="parcels")
    irrigation_flow = models.DecimalField(max_digits=15, decimal_places=2, blank=True, null=True, validators=[])
    
    # External system ID for PD (Pest & Disease) service
    pd_parcel_id = models.IntegerField(blank=True, null=True, help_text="Parcel ID in the Pest & Disease service")

    objects = models.Manager()
    active_objects = ActivePageManager()

    class Meta:
        verbose_name = "Farm Parcel"
        verbose_name_plural = "Farm Parcels"

    def __str__(self):
        return f"{self.farm} - {self.identifier} - ({self.parcel_type})"
    
    def save(self, *args, **kwargs):
        """
        Override save to automatically sync parcels with the PD (Pest & Disease) service.
        
        Handles three scenarios:
        1. CREATE: New parcel creation - creates corresponding parcel in PD
        2. UPDATE: Existing parcel modification - updates parcel in PD
        3. SOFT DELETE: Status changed to DELETED - removes parcel from PD
        """
        import logging
        logger = logging.getLogger(__name__)
        
        # Determine if this is a new parcel
        is_new = self._state.adding
        
        # Avoid recursion when saving pd_parcel_id
        skip_sync = getattr(self, '_skip_pd_sync', False)
        
        # Detect soft delete (status changing to DELETED)
        is_soft_delete = False
        if not is_new and self.pk:
            try:
                old_instance = FarmParcel.objects.get(pk=self.pk)
                is_soft_delete = (old_instance.status != self.BaseModelStatus.DELETED and 
                                 self.status == self.BaseModelStatus.DELETED)
            except FarmParcel.DoesNotExist:
                pass
        
        # Save to database
        super().save(*args, **kwargs)
        
        # Skip PD sync if we're in a recursive save
        if skip_sync:
            return
        
        # SCENARIO 1: Soft Delete - Remove from PD service
        if is_soft_delete and self.pd_parcel_id:
            try:
                from farm_calendar.utils.pd_service import delete_parcel_from_pd
                
                success = delete_parcel_from_pd(self.pd_parcel_id)
                if success:
                    logger.info(f"Parcel {self.pd_parcel_id} removed from PD service (soft delete)")
                else:
                    logger.warning(f"Failed to remove parcel {self.pd_parcel_id} from PD service")
            except Exception as e:
                logger.error(f"Error removing parcel {self.pd_parcel_id} from PD during soft delete: {e}")
            return
        
        # SCENARIO 2: Create - Sync new parcel to PD
        if is_new and not self.pd_parcel_id and self.latitude and self.longitude:
            try:
                from farm_calendar.utils.pd_service import sync_parcel_to_pd
                
                pd_parcel_id = sync_parcel_to_pd(self)
                if pd_parcel_id:
                    # Store PD ID in database (with recursion prevention)
                    self.pd_parcel_id = pd_parcel_id
                    self._skip_pd_sync = True
                    super(FarmParcel, self).save(update_fields=['pd_parcel_id'])
                    self._skip_pd_sync = False
                    logger.info(f"FarmParcel {self.pk} synced to PD with ID {pd_parcel_id}")
                else:
                    logger.warning(f"FarmParcel {self.pk} created but failed to sync to PD")
            except Exception as e:
                logger.error(f"Error syncing FarmParcel {self.pk} to PD: {e}")
        
        # SCENARIO 3: Update - Sync changes to PD
        elif not is_new and self.pd_parcel_id and self.latitude and self.longitude:
            try:
                from farm_calendar.utils.pd_service import update_parcel_in_pd
                
                success = update_parcel_in_pd(self)
                if success:
                    logger.info(f"FarmParcel {self.pk} updated in PD")
                else:
                    logger.warning(f"FarmParcel {self.pk} failed to update in PD")
            except Exception as e:
                logger.error(f"Error updating FarmParcel {self.pk} in PD: {e}")
    
    def delete(self, *args, **kwargs):
        """
        Override delete to remove parcel from PD service when hard deleted.
        
        Note: UI uses soft delete (sets status=DELETED), which is handled in save().
        This handles programmatic hard deletes via .delete().
        """
        import logging
        logger = logging.getLogger(__name__)
        
        pd_parcel_id = self.pd_parcel_id
        
        # Remove from PD service before database deletion
        if pd_parcel_id:
            try:
                from farm_calendar.utils.pd_service import delete_parcel_from_pd
                
                success = delete_parcel_from_pd(pd_parcel_id)
                if success:
                    logger.info(f"Parcel {pd_parcel_id} removed from PD service (hard delete)")
                else:
                    logger.warning(f"Failed to remove parcel {pd_parcel_id} from PD service")
            except Exception as e:
                logger.error(f"Error removing parcel {pd_parcel_id} from PD: {e}")
        
        # Delete from database
        return super().delete(*args, **kwargs)
