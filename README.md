# Code Assessment QLS

Add the QLS Fulfilment and Parcel Service API credentials to your local environment variables to get started:
```
PAKKETDIENST_SQL_BASE_URL=
PAKKETDIENST_SQL_USERNAME=
PAKKETDIENST_SQL_PASSWORD=
```

## TODO
- Separate PDF generation logic into different functions with distinct names and better variable names
- Use the products endpoint to show shipping options on the index and use those values instead of the provided hardcoded values. Additionally store the options in a database/cache to minimize API calls
- Add unit tests for: shipment API service, pdf generation logic
- Add integration test for: the pakbon endpoint, mock external dependencies and make test not rely on the real API
