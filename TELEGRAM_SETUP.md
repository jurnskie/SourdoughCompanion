# Telegram Bot Setup Guide

This guide walks you through setting up Telegram notifications for your Sourdough Companion app.

## Prerequisites

- Telegram app installed on your phone or computer
- Access to your Laravel application's environment configuration

## Step 1: Create a Telegram Bot

1. **Start a chat with BotFather**:
   - Open Telegram and search for `@BotFather`
   - Start a conversation with the official BotFather bot

2. **Create your bot**:
   - Send the command: `/newbot`
   - Choose a name for your bot (e.g., "Sourdough Companion Bot")
   - Choose a unique username ending in "bot" (e.g., "sourdough_companion_bot")

3. **Get your bot token**:
   - BotFather will provide you with a token that looks like: `123456789:ABCdefGHIjklMNOpqrsTUVwxyz`
   - **Keep this token secure** - it's like a password for your bot

## Step 2: Configure Your Laravel App

1. **Add the bot token to your environment**:
   - Open your `.env` file
   - Add this line: `TELEGRAM_BOT_TOKEN=your_token_here`
   - Replace `your_token_here` with the token from BotFather

2. **Example**:
   ```env
   TELEGRAM_BOT_TOKEN=123456789:ABCdefGHIjklMNOpqrsTUVwxyz
   ```

## Step 3: Get Your Chat ID

To receive notifications, you need to get your personal Telegram Chat ID:

1. **Start a chat with your bot**:
   - Search for your bot by its username in Telegram
   - Send any message to your bot (e.g., "Hello")

2. **Get your Chat ID**:
   - Visit this URL in your browser (replace `YOUR_BOT_TOKEN` with your actual token):
     ```
     https://api.telegram.org/botYOUR_BOT_TOKEN/getUpdates
     ```
   
   - Look for the `"chat"` object in the response and find the `"id"` field
   - Your Chat ID will be a number like `123456789`

## Step 4: Add Chat ID to Your Profile

1. **Log into your Sourdough Companion app**
2. **Go to your profile/settings**
3. **Add your Telegram Chat ID** in the appropriate field
4. **Save your settings**

## Step 5: Test Your Setup

1. **Create or update a starter** in the app
2. **Start a bread recipe timer** 
3. **You should receive a Telegram message** confirming the timer has started

## Notification Types

Your bot will send notifications for:

- 🍞 **Starter feeding reminders** - when your starter needs feeding
- ⏰ **Bread baking stages** - bulk fermentation, final proof, baking complete
- 🚨 **Starter health warnings** - if your starter shows concerning signs
- 🔄 **Phase transitions** - when your starter moves between growth phases

## Troubleshooting

### "Bot token is invalid"
- Double-check your bot token in the `.env` file
- Make sure there are no extra spaces or characters
- Verify the token works by visiting: `https://api.telegram.org/botYOUR_TOKEN/getMe`

### "Not receiving messages"
- Verify you've started a conversation with your bot
- Check your Chat ID is correct and saved in your profile
- Make sure your bot token is properly configured
- Check Laravel logs for any notification errors

### "getUpdates returns empty"
- Send a message to your bot first
- The getUpdates endpoint only shows recent messages

## Security Notes

- **Never share your bot token** - treat it like a password
- **Keep your Chat ID private** - it allows sending messages to your account
- Consider using environment-specific bots for development vs production

## Advanced Configuration

### Custom Notification Settings

You can customize notification timing and content by modifying the notification classes in:
- `app/Notifications/FeedingReminderNotification.php`
- `app/Notifications/BreadProofingNotification.php`  
- `app/Notifications/PhaseTransitionNotification.php`
- `app/Notifications/StarterHealthNotification.php`

### Webhook Setup (Optional)

For production environments, consider setting up webhooks instead of polling:

```bash
curl -X POST "https://api.telegram.org/botYOUR_TOKEN/setWebhook" \
     -d "url=https://yourdomain.com/telegram/webhook"
```

## Comparison with Previous Signal Setup

This Telegram setup is much simpler than the previous Signal implementation:

- ❌ **Signal**: Required Docker containers, device linking, phone number registration
- ✅ **Telegram**: Simple bot creation, just need a token and chat ID
- ❌ **Signal**: Complex infrastructure setup on Synology NAS
- ✅ **Telegram**: No additional infrastructure required
- ❌ **Signal**: Limited formatting options
- ✅ **Telegram**: Rich message formatting with Markdown, emojis, and buttons

## Support

If you encounter issues:

1. Check the Laravel logs: `tail -f storage/logs/laravel.log`
2. Verify queue workers are running: `php artisan queue:work`
3. Test bot connectivity: `https://api.telegram.org/botYOUR_TOKEN/getMe`