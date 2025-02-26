# Code Assessment QLS

Add the QLS Fulfilment and Parcel Service API credentials to your environment variables to get started:
```
PAKKETDIENST_SQL_USER=
PAKKETDIENST_SQL_PASSWORD=
```

## TODO
- Use Symfony HttpClient instead of cURL for API communication for easier testing
- Extract shipment API logic into a dedicated service for better reusability
- Seperate PDF generation logic into different functions with distinct names and better variable names
- Use the products endpoint to show shipping options on the index and use those values instead of the provided hardcoded values. Additionally store the options in a database/cache to minimize API calls
- Add unit tests for: shipment API service, pdf generation logic
- Add integration test for: the pakbon endpoint, mock external dependancies an make test not rely on the real API
