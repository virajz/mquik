# 3 Aug 2026

## Customer (Done)

- Need to add company name in the identity block of customer
- If the GST type or number is added, company name should be mandatory
- Company name should be searchable as well
- After saving the customer, show the popup to redirect to add vehicle, with that customer selected.
- When unregistered type is selected, show unregistered.

## Vehcile Colors (Done)

- Show the notes outside

## Appointment

- allow to add vehicle like customer
- sync new address to the customer master
- Change order of section (address first, then other sections)
- Change order for service and routing
    - First Department, based on that we will show the service type, advisor and technician

## Pick up and drop

- customer is getting blank, but still shows the vehicle
- remove label, courier
- how one can compelte the pick up and drop?
- Need a histroy of tracking things of when the driver was assigned and when the car arrived
- need a driver wise daily summary for list of cars driver has for a day
- need a whole journy of the car - from customer calling to car inward (and every process in between)

# 4 Aug 2026

## Spare Master (Done)

- Improve search features
- Vehcile and Variant search filter
- Need Department filter as well
- Type should be the first on this page, then related block
- then Identity
- Optional expiry date in spares
- Manufacturing Date, shelf life (could be month and life as well)
- FIFO while consuming, when the code is scanned to issue part we should notify that the part is left, and user must confirm
- Provide Option to add HSN from here
- Live validation of part number
- Need a smarter and fast way to select multiple cars using a popup, right now we are allowing to select onlt a few cars to select, one at a time

## Inventory Search (Done)

- provide a filter for non zero items

## Internal Parts Inquiry (Done)

- combine job card, customer name and vehicle number
- remove Vendor

# 11 Aug 2026

## Dashboard

- we need a red, orange and green zones for cars whose TAT increases. For example, for a job card I have setup a date of delivery today. So till today it will be in the green zone, but from tomorrow it will be in orange zone, if the car stays in the orange zone for 2 days, then it will be in the red zone. In most of the cases dates won't be changed for delivery.

- First in the job card, we only add estimated date of delivery. Final date of delivery should be added only after the estimate is approved by client. So that we can analyze what went wrong if the car was not delivered on time. and also everyone is clear about how things are going to get shaped.

- Integrate VIN search with parts and estimated services related to km s of the car.

- We need inter department cross sale - for example if a car comes, and it has mechanical service, we can suggest for the tyre replaement or value added services, which are genuinely good for customer.
