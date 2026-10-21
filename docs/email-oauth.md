# Email Account OAuth Setup

Connect Gmail and Outlook with OAuth. SMTP accounts continue to use a password form. Gmail and Outlook passwords are never requested or stored.

## Environment variables

Add these values to `.env`. Do not commit secrets.

```
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI="${APP_URL}/master/email-accounts/oauth/gmail/callback"

MICROSOFT_CLIENT_ID=
MICROSOFT_CLIENT_SECRET=
MICROSOFT_REDIRECT_URI="${APP_URL}/master/email-accounts/oauth/outlook/callback"
MICROSOFT_TENANT_ID=common
```

`APP_KEY` is required so Laravel can encrypt access and refresh tokens.

`MICROSOFT_TENANT_ID` defaults to `common` (work, school, and personal Microsoft accounts). Set a directory ID only when the app should be limited to one Microsoft tenant.

Redirect URIs must match the values registered with Google and Microsoft exactly, including scheme and host.

The application sends the callback URL from the **current browser origin**, not from `APP_URL`. If you open the app as `http://ems.test` and `APP_URL` is `http://localhost:8000`, Google/Microsoft still receive the `ems.test` callback. Register every origin you actually use.

## Google Cloud Console

1. Create an OAuth client ID of type **Web application**.
2. Add each authorized redirect URI you use locally:

```
http://ems.test/master/email-accounts/oauth/gmail/callback
http://localhost:8000/master/email-accounts/oauth/gmail/callback
```

Production example:

```
https://your-domain/master/email-accounts/oauth/gmail/callback
```

3. Enable the Gmail API.
4. Copy the client ID and client secret into `.env`.

### Gmail scopes

- `openid`
- `email`
- `profile`
- `https://www.googleapis.com/auth/gmail.send`

These cover account identity and sending. Request additional Gmail scopes later only if mailbox sync is implemented.

## Microsoft Entra / Azure App Registration

1. Register a web application.
2. Add each redirect URI under **Web**:

```
http://ems.test/master/email-accounts/oauth/outlook/callback
http://localhost:8000/master/email-accounts/oauth/outlook/callback
```

Production example:

```
https://your-domain/master/email-accounts/oauth/outlook/callback
```

3. Create a client secret.
4. Grant delegated Microsoft Graph permissions:
   - `openid`
   - `offline_access`
   - `User.Read`
   - `Mail.Send`
5. Copy the application (client) ID and secret into `.env`.

## Scheduler

Token refresh uses the existing Laravel scheduler. No second scheduler is added.

Command:

```
php artisan email:refresh-oauth-tokens
```

Schedule: every 15 minutes, `withoutOverlapping(30)`.

Required cron on the server (Laravel's standard scheduler entry):

```
* * * * * cd /path/to/ems && php artisan schedule:run >> /dev/null 2>&1
```

A queue worker is not required for token refresh. The command runs synchronously.

Email send/read operations should also call `OAuthConnectionService::ensureValidAccessToken()` so an expired token is refreshed even if the scheduler has not run yet.

## Reconnect

If Google or Microsoft revokes access, or refresh fails, the account is marked **Reconnect required**. Use **Reconnect** on the email account page to run OAuth again. Disconnect clears stored tokens and also requires reconnection.
