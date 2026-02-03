# Africa's Talking SMS Configuration

## Overview
The Chabrin Lease Management System uses Africa's Talking to send OTP codes for digital signature verification and lease approval notifications.

## Configuration Steps

### 1. Add Credentials to .env File

Open your `.env` file in the root directory and add the following lines:

```env
# Africa's Talking SMS Configuration
AFRICAS_TALKING_USERNAME=your-username-here
AFRICAS_TALKING_API_KEY=your-api-key-here
AFRICAS_TALKING_SHORTCODE=your-shortcode-here
```

> **SECURITY WARNING**: Never commit real credentials to version control. Always use environment variables.

### 2. Clear Configuration Cache

After updating your `.env` file, run:

```bash
php artisan config:clear
```

### 3. Test SMS Functionality

You can test SMS sending by:

1. Creating a new lease
2. Selecting "Digital Signing" mode
3. Requesting an OTP for tenant signature
4. The system will send a 4-digit code via SMS to the tenant's phone number

## How It Works

### OTP Generation
- **Code**: 4-digit random number
- **Validity**: 10 minutes
- **Rate Limit**: Maximum 3 OTP requests per hour per lease

### Phone Number Formatting
The system automatically formats phone numbers:
- `0712345678` → `+254712345678`
- `712345678` → `+254712345678`
- `+254712345678` → `+254712345678` (no change)

### SMS Message Template
```
Your Chabrin Lease verification code is: {CODE}. Valid for 10 minutes. Ref: {LEASE_REFERENCE}
```

## Features Using SMS

1. **Digital Signature Verification**
   - Tenant signs lease digitally
   - OTP sent to tenant's phone
   - Tenant enters code to confirm identity
   - Signature recorded with timestamp and location

2. **Landlord Approval Notifications** (Future)
   - Notify landlords of pending lease approvals
   - Send approval status updates

## Troubleshooting

### SMS Not Sending

**Check Logs:**
```bash
tail -f storage/logs/laravel.log
```

**Common Issues:**
1. **API credentials not configured**: Ensure `.env` has correct credentials
2. **Invalid phone number**: Must be valid Kenyan mobile number (07XX or +2547XX)
3. **Insufficient credit**: Check your Africa's Talking account balance
4. **Rate limiting**: Max 3 OTPs per hour per lease

### Testing Without Live SMS

For development/testing, if Africa's Talking is not configured, the system will:
- Log the OTP code to `storage/logs/laravel.log`
- Continue with the signing flow
- Look for: `Africa's Talking not configured - OTP would be: XXXX`

## Getting Your Credentials

1. Sign up at https://account.africastalking.com/
2. Create an application
3. Go to Settings → API Key to generate your key
4. Request a shortcode/sender ID if needed

## Security Notes

- Never commit API keys or credentials to Git
- Use `.env` files for all sensitive configuration
- OTP codes expire after 10 minutes
- Maximum 3 verification attempts per OTP
- Rate limiting prevents OTP spam
- All OTP activity is logged for audit
- Old OTPs (30+ days) are automatically cleaned up

## API Documentation

For more details on Africa's Talking SMS API:
- https://developers.africastalking.com/docs/sms/overview
- https://account.africastalking.com/ (Account dashboard)
