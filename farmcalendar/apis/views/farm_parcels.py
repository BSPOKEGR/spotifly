from rest_framework import permissions, viewsets
from rest_framework.response import Response
from rest_framework import status as http_status
from farm_management.models import (
    Farm,
    FarmParcel,
)
from ..serializers import (
    FarmSerializer,
    FarmParcelSerializer,
)

from ..filters import FarmParcelFilter

class FarmViewSet(viewsets.ModelViewSet):
    """
    API endpoint that allows Farm to be viewed or edited.
    """
    queryset = Farm.objects.all().prefetch_related('farm_parcels').order_by('-created_at')
    serializer_class = FarmSerializer
    permission_classes = [permissions.AllowAny]
    filterset_fields = ['name', 'status']


class FarmParcelViewSet(viewsets.ModelViewSet):
    """
    API endpoint for Farm Parcel CRUD operations.
    
    Parcels are automatically synchronized with the PD (Pest & Disease) service:
    - CREATE: New parcels are registered in PD
    - UPDATE: Changes are propagated to PD
    - DELETE (soft): Removes from PD when status set to DELETED
    
    Synchronization is handled via model-level save/delete hooks.
    """
    queryset = FarmParcel.objects.all().prefetch_related('farmcrops').order_by('-created_at')
    serializer_class = FarmParcelSerializer
    permission_classes = [permissions.AllowAny]

    filterset_class = FarmParcelFilter
