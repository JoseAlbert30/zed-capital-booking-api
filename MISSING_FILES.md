# Missing Files for Production

## Required Letterhead Images

Upload these files to your production server at: `/var/www/zed-capital-booking-api/public/storage/letterheads/`

1. `viera-black.png` - Viera Residences logo (black version)
2. `vantage-black.png` - Vantage logo (black version)

### Upload Command

```bash
# On your local machine, navigate to where you have the letterhead images
# Then SCP them to the server:

scp viera-black.png deploy@your-server:/var/www/zed-capital-booking-api/storage/app/public/letterheads/
scp vantage-black.png deploy@your-server:/var/www/zed-capital-booking-api/storage/app/public/letterheads/

# Then on the server, ensure the storage link exists:
cd /var/www/zed-capital-booking-api
php artisan storage:link
```

## Required Escrow Document

Upload to: `/var/www/zed-capital-booking-api/storage/app/public/handover-notice-attachments/viera-residences/`

1. `Viera Residences - Escrow Acc.pdf`

```bash
# Create the directory first
ssh deploy@your-server
mkdir -p /var/www/zed-capital-booking-api/storage/app/public/handover-notice-attachments/viera-residences

# Then upload
scp "Viera Residences - Escrow Acc.pdf" deploy@your-server:/var/www/zed-capital-booking-api/storage/app/public/handover-notice-attachments/viera-residences/
```

## Verify Files Exist

```bash
ssh deploy@your-server
cd /var/www/zed-capital-booking-api
ls -la public/storage/letterheads/
ls -la storage/app/public/handover-notice-attachments/viera-residences/
```
