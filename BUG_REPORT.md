| Field | Description |
|-------|-------------|
| **Ticket** | AH-101 |
| **Root Cause** | Data includes all porjects including not from a specific project |
| **Fix Applied** | Include projectId param to all functions to filter proejct |
| **Regression Test** | Create 2 different projects and their notifications |
| **Prevention** | Ensure usage of proper where() in query |
|
|-------|-------------|
| **Ticket** | AH-102 |
| **Root Cause** | Subscriber may not have email at times and highload webhook, may create dupliate or no subscribers |
| **Fix Applied** | Use external_id as identifier when email is null, use cache lock instead of get to reduce highload error |
| **Regression Test** | Create 2 subscribers without emails. Check cache lock works with same identifier |
| **Prevention** | Ensure failsafe indentifier is present, Use cache lock for highload |
|
|-------|-------------|
| **Ticket** | AH-103 |
| **Root Cause** | Wrong assigned priority and scheduledWindow is always null |
| **Fix Applied** | Rearrange listener sequence |
| **Regression Test** | Check if all listeners work accordingly |
| **Prevention** | Correct sequencing to required listeners |
|
|-------|-------------|
| **Ticket** | AH-104 |
| **Root Cause** | Pending notifications not filtered properly |
                | uniqueId generated in ProcessAlertDigest only allowed one job per day |
| **Fix Applied** | Filter notifications based on sent, uniqueId created based on alertIds |
| **Regression Test** | Create pending notifications for same day |
| **Prevention** | Uniqueid has stay unique but not inteferring with processes |
