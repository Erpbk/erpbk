# GET YOUR WHATSAPP GROUP ID - SIMPLE STEPS

## The service is ALREADY RUNNING on your computer! ✅

Follow these simple steps:

---

## STEP 1: Open the Groups Page in Your Browser

**Option A:** Double-click this file in File Explorer:
```
D:\xammp1\htdocs\erpbk\show-groups.html
```

**Option B:** Open your browser and go to:
```
http://localhost:3001/api/groups
```
You'll see JSON data with your groups.

---

## STEP 2: Find Your Group ID

### If you used Option A (show-groups.html):
- You'll see a nice webpage with all your WhatsApp groups
- Find the group you want (e.g., "Bike Riders", "Delivery Team", etc.)
- Click the "Copy ID" button next to that group
- The ID will be copied to your clipboard!

### If you used Option B (JSON view):
Look for something like:
```json
{
  "success": true,
  "count": 5,
  "groups": [
    {
      "id": "123456789-1234567890@g.us",
      "name": "Your Group Name",
      "participants": 10
    }
  ]
}
```
- Find your target group in the list
- Copy the "id" value (it ends with @g.us)

---

## STEP 3: Set the Group ID in .env

1. Open this file in Notepad or your editor:
   ```
   D:\xammp1\htdocs\erpbk\whatsapp-service\.env
   ```

2. Find the line that says:
   ```
   WHATSAPP_GROUP_ID=
   ```

3. Paste your group ID after the equals sign:
   ```
   WHATSAPP_GROUP_ID=123456789-1234567890@g.us
   ```

4. **SAVE THE FILE!**

---

## STEP 4: Restart the Node Service

### In your Git Bash or Command Prompt window:

1. **Stop the current service:**
   - Press `Ctrl + C` in the window where Node is running

2. **Start it again:**
   ```bash
   cd D:\xammp1\htdocs\erpbk\whatsapp-service
   npm start
   ```

---

## STEP 5: Test by Assigning a Bike! 🏍

1. Go to your Laravel application
2. Assign a bike to a rider
3. Make sure the warehouse status is "Active"
4. The WhatsApp message will be sent automatically to your group!

---

## TROUBLESHOOTING

### "Error: Could not connect" in browser
- The service might not be running
- Or WhatsApp is not connected
- Run: `npm start` in the whatsapp-service directory

### "No groups found"
- WhatsApp might not be logged in
- Check if you scanned the QR code
- Look at the Node console for "✓ WhatsApp authenticated successfully!"

### Port already in use error
- Good! It means the service is already running
- Just skip starting it again
- Go directly to the browser steps above

---

## NEED HELP?

Just paste the group ID you found here and I'll update the .env file for you automatically!

