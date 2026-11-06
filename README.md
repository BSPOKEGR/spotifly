# Spotifly

Spotifly is an agricultural platform that combines weather data, pest and disease
risk prediction, and farm activity management to help farmers make better
decisions. This repository brings together all of the services that make up
the platform.

## Structure

| Folder | Description |
| --- | --- |
| [`api`](./api) | Spotifly backend API (Laravel) and its deployment orchestrator |
| [`client`](./client) | Web frontend (Vue 3 + TypeScript + Vite) |
| [`farmcalendar`](./farmcalendar) | Digital farm calendar service — activities, assets, and parcels |
| [`gatekeeper`](./gatekeeper) | Central authentication/validation gateway for the microservices |
| [`pd`](./pd) | Pest and Disease Management service — risk models for pests and diseases |
| [`weatherservice`](./weatherservice) | Weather API — forecasts and agricultural indicators (FastAPI) |

Each folder has its own README with setup and usage instructions specific to
that service.

## Acknowledgements

`farmcalendar`, `gatekeeper`, `pd`, and `weatherservice` were created in the
context of the [OpenAgri project](https://horizon-openagri.eu/). OpenAgri has
received funding from the EU's Horizon Europe research and innovation
programme under Grant Agreement no. 101134083.
