# Item Approval User Manual

## 1. Purpose

This app manages item creation requests from submission to D365 creation.

## 2. Roles

### Requester
- Create new item request
- Revise rejected request
- Answer Accounting info request
- Track request status and history

### Accounting
- Review new requests
- Classify item group, model group, service category, stocked/non-stocked
- Request more info
- Reject request

### Commercial
- Review classified requests
- Trigger item creation in D365
- Review creation errors

## 3. Request lifecycle

1. **Pending (Accounting)**
   - Request submitted
   - Accounting reviews it

2. **Needs Info (Accounting)**
   - Accounting needs clarification
   - Requester must open same request and add response
   - Save or click **Respond to Accounting**
   - Request returns to `pending`

3. **Classified (Commercial)**
   - Accounting assigned item group and other classification fields
   - Commercial can create item in D365

4. **Creating in D365**
   - Job runs in background

5. **Created in D365**
   - Item created successfully

6. **Rejected**
   - Requester can revise and resubmit

7. **Create Failed**
   - D365 creation failed
   - Commercial can inspect error and retry as needed

## 4. How requester answers Accounting

1. Open your request.
2. Read **Accounting requested more information**.
3. Read **Accounting clarification** note.
4. Fill **Your response to Accounting**.
5. Update requested item fields if needed.
6. Click **Respond to Accounting** or **Save**.

After save:
- status changes from `needs_info` to `pending`
- Accounting sees request again
- response appears in history

## 5. How requester revises rejected request

1. Open rejected request.
2. Read rejection reason.
3. Edit allowed fields.
4. Save.
5. Status returns to `pending`.

## 6. How Accounting works

### Classify
Use **Classify & Assign** to set:
- Item Group
- Item Model Group
- Item/Service Category
- Stocked / Non-stocked

### Request info
Use **Request Info** to send clarification back to requester.

### Reject
Use **Reject** to stop request and give reason.

## 7. How Commercial works

### Create in D365
When status is `classified` or `create_failed`, Commercial can use **Create in D365**.

### View error
If creation failed, open **View Error** to see D365 failure details.

## 8. History

Each request keeps history:
- who changed it
- when it changed
- from status
- to status
- note / details
- requester response when answering Accounting

## 9. Dashboard cues

- Badge count shows requests waiting on your role
- Aging items are highlighted after 48 hours in current stage

## 10. Common issues

### I do not see Respond to Accounting
- Request must be in `needs_info`
- You must open edit page for same request

### Save fails with missing column error
- Run database migrations

### Request does not appear in Commercial queue
- Accounting must set status to `classified`
