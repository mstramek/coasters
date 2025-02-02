## Uruchomienie

### Uruchomienie DEV:

1) Ustawić dopuszczalne adresy IP, plik `.env.development`, zmienna `ALLOWED_IPS`
2) Uruchomić polecenie
 ```sh
 docker-compose up --force-recreate
 ```
Wersja DEV opiera się o bazę nr 1 w Redis.

### Uruchomienie PROD:
```sh
docker-compose -f docker-compose.yaml up --force-recreate
```
Wersja PROD opiera się o bazę nr 2 w Redis.

## Zarządzanie danymi
### Dodawanie kolejki
```sh
curl --location 'http://localhost:8888/api/coasters' \
--header 'Content-Type: application/json' \
--data '{
    "numberOfPersonnel": 10,
    "numberOfCustomers": 10,
    "routeLength": 1000,
    "hoursFrom": "08:00",
    "hoursTo": "16:00"
}'
```
### Edycja kolejki
Payload nie musi być pełny, można przekazać pojedyncze parametry.
```sh
curl --location --request PUT 'http://localhost:8888/api/coasters/A1' \
--header 'Content-Type: application/json' \
--data '{
    "numberOfPersonnel": 15,
    "numberOfCustomers": 2000,
    "hoursFrom": "08:00",
    "hoursTo": "16:00"
}'
```

### Dodawanie wagonu do kolejki
```sh
curl --location 'http://localhost:8888/api/coasters/A1/wagons' \
--header 'Content-Type: application/json' \
--data '{
    "numberOfSeats": 20,
    "speed": 1.2
}'
```
### Usuwanie wagonu
```sh
curl --location --request DELETE 'http://localhost:8888/api/coasters/A1/wagons/W1'
```

## Monitoring
- Wszystkie statystyki zrzucane są do STDOUT kontenera `roller_coaster_monitoring`.
- Błędy dodatkowo zrzucane są do logu plikowego (logi PROD w innym miejscu niż DEV)
- Nowa informacja o bieżącym stanie wszystkich kolejek wyświetlana jest w sytuacji gdy zmienią się parametry
którejkolwiek z  kolejek.
- Monitorowanie statystyk:
    ```sh
    docker logs roller_coaster_monitoring --follow
    ```
