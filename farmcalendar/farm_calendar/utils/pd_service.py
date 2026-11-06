"""
Service for synchronizing parcels with the Pest & Disease (PD) microservice.
"""
import os
import requests
import logging
from typing import Optional, Dict, Any
from django.conf import settings

logger = logging.getLogger(__name__)

# PD service configuration
PD_SERVICE_URL = os.getenv('PD_SERVICE_URL', 'http://pdm:8003')
PD_API_BASE = f"{PD_SERVICE_URL}/api/v1"
GATEKEEPER_API_LOGIN_URL = os.getenv('GATEKEEPER_API_LOGIN_URL', 'http://django-gatekeeper:8001/api/login/')


class PDServiceClient:
    """
    Client for interacting with the PD (Pest & Disease) service API.
    
    Provides methods to:
    - Create parcels in PD service
    - Update existing parcels
    - Delete parcels
    - Retrieve parcel information
    
    Authentication is handled automatically via Gatekeeper JWT tokens.
    """
    
    _access_token = None
    
    @staticmethod
    def _get_gatekeeper_token() -> Optional[str]:
        """Get JWT token from Gatekeeper for authentication"""
        try:
            # Use the FarmCalendar's Gatekeeper credentials
            username = os.getenv('FARMCALENDAR_GATEKEEPER_USER', 'spadmin')
            password = os.getenv('FARMCALENDAR_GATEKEEPER_PASSWORD')
            if not password:
                logger.error(
                    "FARMCALENDAR_GATEKEEPER_PASSWORD is not set; "
                    "cannot authenticate against Gatekeeper"
                )
                return None
            
            response = requests.post(
                GATEKEEPER_API_LOGIN_URL,
                json={
                    'username': username,
                    'password': password
                },
                timeout=10
            )
            
            if response.status_code == 200:
                data = response.json()
                PDServiceClient._access_token = data.get('access')  # Gatekeeper returns 'access' not 'access_token'
                return PDServiceClient._access_token
            else:
                logger.error(f"Failed to get Gatekeeper token: {response.status_code}")
                return None
                
        except requests.exceptions.RequestException as e:
            logger.error(f"Error getting Gatekeeper token: {e}")
            return None
    
    @staticmethod
    def _get_headers() -> Dict[str, str]:
        """Get headers for PD API requests with authentication"""
        # Get or refresh token
        if not PDServiceClient._access_token:
            PDServiceClient._get_gatekeeper_token()
        
        headers = {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        }
        
        if PDServiceClient._access_token:
            headers['Authorization'] = f'Bearer {PDServiceClient._access_token}'
        
        return headers
    
    @staticmethod
    def _retry_with_new_token(func):
        """Decorator to retry API call with fresh token if authentication fails"""
        def wrapper(*args, **kwargs):
            try:
                return func(*args, **kwargs)
            except Exception as e:
                # If we get auth error, try refreshing token once
                if '401' in str(e) or '403' in str(e):
                    logger.info("Auth failed, refreshing token and retrying...")
                    PDServiceClient._access_token = None
                    PDServiceClient._get_gatekeeper_token()
                    return func(*args, **kwargs)
                raise
        return wrapper
    
    @staticmethod
    def create_parcel(latitude: float, longitude: float, name: str) -> Optional[int]:
        """
        Create a parcel in the PD service
        
        Args:
            latitude: Parcel centroid latitude
            longitude: Parcel centroid longitude
            name: Parcel name/identifier
            
        Returns:
            PD parcel ID (integer) if successful, None otherwise
        """
        try:
            url = f"{PD_API_BASE}/parcel/"
            payload = {
                "latitude": latitude,
                "longitude": longitude,
                "name": name
            }
            
            logger.info(f"Creating parcel in PD: {name} at ({latitude}, {longitude})")
            
            response = requests.post(
                url,
                json=payload,
                headers=PDServiceClient._get_headers(),
                timeout=10
            )
            
            logger.info(f"PD API response status: {response.status_code}")
            
            if response.status_code == 401 or response.status_code == 403:
                # Token expired, refresh and retry
                logger.info("Authentication failed, refreshing token...")
                PDServiceClient._access_token = None
                response = requests.post(
                    url,
                    json=payload,
                    headers=PDServiceClient._get_headers(),
                    timeout=10
                )
            
            if response.status_code == 200:
                # The API returns a Message with message text, not the parcel object
                # We need to query back to get the parcel ID
                logger.info(f"Parcel created in PD, fetching ID for: {name}")
                parcels = PDServiceClient.get_all_parcels()
                for parcel in parcels:
                    if parcel.get('name') == name:
                        parcel_id = parcel.get('id')
                        logger.info(f"Found PD parcel ID: {parcel_id}")
                        return parcel_id
                
                logger.warning(f"Parcel created in PD but couldn't retrieve ID for: {name}")
                return None
            else:
                logger.error(f"Failed to create parcel in PD: {response.status_code} - {response.text}")
                return None
                
        except requests.exceptions.RequestException as e:
            logger.error(f"Error calling PD service to create parcel: {e}")
            return None
    
    @staticmethod
    def get_all_parcels() -> list:
        """
        Get all parcels from PD service
        
        Returns:
            List of parcel dictionaries
        """
        try:
            url = f"{PD_API_BASE}/parcel/"
            response = requests.get(
                url,
                headers=PDServiceClient._get_headers(),
                timeout=10
            )
            
            if response.status_code == 401 or response.status_code == 403:
                # Token expired, refresh and retry
                PDServiceClient._access_token = None
                response = requests.get(
                    url,
                    headers=PDServiceClient._get_headers(),
                    timeout=10
                )
            
            if response.status_code == 200:
                data = response.json()
                return data.get('elements', [])
            else:
                logger.error(f"Failed to get parcels from PD: {response.status_code}")
                return []
                
        except requests.exceptions.RequestException as e:
            logger.error(f"Error calling PD service to get parcels: {e}")
            return []
    
    @staticmethod
    def update_parcel(pd_parcel_id: int, latitude: float, longitude: float, name: str) -> bool:
        """
        Update a parcel in the PD service using PUT
        
        Args:
            pd_parcel_id: The integer ID of the parcel in PD service
            latitude: New parcel centroid latitude
            longitude: New parcel centroid longitude
            name: New parcel name/identifier
            
        Returns:
            True if successful, False otherwise
        """
        try:
            url = f"{PD_API_BASE}/parcel/{pd_parcel_id}/"
            payload = {
                "latitude": latitude,
                "longitude": longitude,
                "name": name
            }
            
            logger.info(f"Updating parcel {pd_parcel_id} in PD: {name} at ({latitude}, {longitude})")
            
            response = requests.put(
                url,
                json=payload,
                headers=PDServiceClient._get_headers(),
                timeout=10
            )
            
            logger.info(f"PD API update response status: {response.status_code}")
            
            if response.status_code == 401 or response.status_code == 403:
                # Token expired, refresh and retry
                logger.info("Authentication failed, refreshing token...")
                PDServiceClient._access_token = None
                response = requests.put(
                    url,
                    json=payload,
                    headers=PDServiceClient._get_headers(),
                    timeout=10
                )
            
            if response.status_code == 200:
                logger.info(f"Successfully updated parcel {pd_parcel_id} in PD")
                return True
            else:
                logger.error(f"Failed to update parcel in PD: {response.status_code} - {response.text}")
                return False
                
        except requests.exceptions.RequestException as e:
            logger.error(f"Error calling PD service to update parcel: {e}")
            return False
    
    @staticmethod
    def delete_parcel(pd_parcel_id: int) -> bool:
        """
        Delete a parcel from the PD service
        
        Args:
            pd_parcel_id: The integer ID of the parcel in PD service
            
        Returns:
            True if successful, False otherwise
        """
        try:
            url = f"{PD_API_BASE}/parcel/{pd_parcel_id}/"
            response = requests.delete(
                url,
                headers=PDServiceClient._get_headers(),
                timeout=10
            )
            
            if response.status_code == 401 or response.status_code == 403:
                # Token expired, refresh and retry
                PDServiceClient._access_token = None
                response = requests.delete(
                    url,
                    headers=PDServiceClient._get_headers(),
                    timeout=10
                )
            
            if response.status_code == 200:
                logger.info(f"Successfully deleted parcel {pd_parcel_id} from PD")
                return True
            else:
                logger.error(f"Failed to delete parcel from PD: {response.status_code} - {response.text}")
                return False
                
        except requests.exceptions.RequestException as e:
            logger.error(f"Error calling PD service to delete parcel: {e}")
            return False


def sync_parcel_to_pd(farm_parcel) -> Optional[int]:
    """
    Sync a FarmParcel to the PD service
    
    Args:
        farm_parcel: FarmParcel model instance
        
    Returns:
        PD parcel ID if successful, None otherwise
    """
    if not farm_parcel.latitude or not farm_parcel.longitude:
        logger.warning(f"FarmParcel {farm_parcel.id} has no coordinates, skipping PD sync")
        return None
    
    # Use identifier as the name for PD
    name = farm_parcel.identifier or f"Parcel-{farm_parcel.id}"
    
    pd_parcel_id = PDServiceClient.create_parcel(
        latitude=float(farm_parcel.latitude),
        longitude=float(farm_parcel.longitude),
        name=name
    )
    
    return pd_parcel_id


def delete_parcel_from_pd(pd_parcel_id: int) -> bool:
    """
    Delete a parcel from the PD service
    
    Args:
        pd_parcel_id: The integer ID of the parcel in PD service
        
    Returns:
        True if successful, False otherwise
    """
    return PDServiceClient.delete_parcel(pd_parcel_id)


def update_parcel_in_pd(farm_parcel) -> bool:
    """
    Update a parcel in the PD service
    
    Args:
        farm_parcel: FarmParcel model instance
        
    Returns:
        True if successful, False otherwise
    """
    if not farm_parcel.pd_parcel_id:
        logger.warning(f"FarmParcel {farm_parcel.id} has no PD ID, cannot update")
        return False
    
    if not farm_parcel.latitude or not farm_parcel.longitude:
        logger.warning(f"FarmParcel {farm_parcel.id} has no coordinates, cannot update PD")
        return False
    
    # Use identifier as the name for PD
    name = farm_parcel.identifier or f"Parcel-{farm_parcel.id}"
    
    return PDServiceClient.update_parcel(
        pd_parcel_id=farm_parcel.pd_parcel_id,
        latitude=float(farm_parcel.latitude),
        longitude=float(farm_parcel.longitude),
        name=name
    )
