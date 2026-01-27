# Quick Start - User Access & Login

## 🚀 Start Using the System

### 1. Access the Admin Panel
- Open your browser and go to: `http://127.0.0.1:8000/admin`
- You'll see the login page

### 2. Login with Demo Account

Choose one of these accounts:

| Account | Email | Password | Access |
|---------|-------|----------|--------|
| 👑 Super Admin | `admin@chabrin.test` | `admin123` | Everything + user management |
| 📋 Manager | `manager@chabrin.test` | `manager123` | All leases, reports |
| 👨‍💼 Agent | `agent+1@chabrin.test` | `agent123` | Own leases, documents |

### 3. After Login
You'll see the dashboard with access to:
- 📄 **Leases** - View, edit, create leases
- 👥 **Tenants** - Manage tenant information
- 🏠 **Properties** - Property management
- 🏘️ **Units** - Unit/apartment management
- 🏢 **Landlords** - Landlord information
- 👤 **Users** *(admin only)* - Create and manage users

## 👑 Super Admin Features (admin@chabrin.test)

As a super admin, you can:

### Manage Users
1. Go to **Users** menu item
2. Click **New User** button
3. Fill in:
   - Name
   - Email
   - Password
   - Role (super_admin, admin, manager, agent, viewer)
   - Phone (optional)
   - Department (optional)
4. Click **Create**

### Edit User
1. Go to **Users**
2. Click on user row to edit
3. Change any field and **Save**
4. Or click **Delete** to remove user

### View Login History
- Click on user row
- See **Last Login At** field showing when they last accessed the system

## 📋 Manager Features (manager@chabrin.test)

- View all leases
- Create new leases
- Edit existing leases
- Export lease documents
- View reports and analytics
- ❌ Cannot manage users

## 👨‍💼 Agent Features (agent+1@chabrin.test)

- Create and view leases
- Download lease documents as PDF
- View assigned tenants
- Track lease status
- ❌ Cannot manage other users
- ❌ Cannot access user management

## 🔒 Security Features

✅ **Password Protection**
- All passwords are encrypted
- Can only be changed by user or super_admin

✅ **Session Security**
- Sessions expire after 2 hours of inactivity
- Automatic logout for security
- CSRF protection on all forms

✅ **Role-Based Access**
- Features hidden based on your role
- Some routes automatically blocked
- Menu items only show if you have permission

## ❓ Frequently Asked Questions

### Q: I forgot my password. What do I do?
**A:** For now, ask a super_admin to:
1. Go to Users
2. Find your email
3. Edit and set a new password

*(Password reset email coming soon)*

### Q: Can I change my own password?
**A:** Coming soon with a "Change Password" feature in the Account menu

### Q: Why can't I see the Users menu?
**A:** Only super_admin accounts can manage users. Ask your administrator to create an admin account for you.

### Q: Can I have multiple roles?
**A:** Currently each user has one primary role. Advanced role combinations coming soon.

### Q: What if the system logs me out unexpectedly?
**A:** Sessions expire after 2 hours of inactivity. Just log back in with your email and password.

### Q: Can I delete my own account?
**A:** No, only super_admin can delete accounts (to prevent accidental lockout).

### Q: What happens when I logout?
**A:** Your session is ended, and you'll need to log in again to access protected areas.

## 📊 System Architecture

```
User Login → Filament Auth → PostgreSQL Check → Session Created → Dashboard
                                ↓
                         Is Password Valid?
                         ↙                    ↘
                      Yes                      No
                      ↓                         ↓
                   Logged In              Try Again
```

## 🔑 Key Roles Explained

### 👑 Super Admin
- **What**: System administrator with full control
- **Can Do**: Manage everything including users
- **Best For**: Technical administrators, system owners
- **Count**: Should have at least 1, usually 1-2 people

### 📋 Admin
- **What**: Senior manager with broad access
- **Can Do**: Manage leases, properties, reports, but not users
- **Best For**: Department heads, operations managers
- **Count**: Usually 1-3 people

### 👔 Manager
- **What**: Team lead with operational access
- **Can Do**: All lease operations, team management
- **Best For**: Property managers, office managers
- **Count**: Usually 2-5 people

### 👨‍💼 Agent
- **What**: Field staff with focused access
- **Can Do**: Create leases, manage documents
- **Best For**: Leasing agents, field coordinators
- **Count**: Unlimited, usually 5-20 people

### 👁️ Viewer
- **What**: Read-only access for reporting
- **Can Do**: View dashboards, reports, analytics
- **Best For**: Executives, auditors, read-only access
- **Count**: As needed for reporting

## 🆘 Troubleshooting

### Login page not loading
- Is the server running? Check: `http://127.0.0.1:8000`
- Try reloading the page (Ctrl+R)

### Credentials not working
- Check email spelling exactly
- Password is case-sensitive
- Verify account is active (not deactivated)

### Cannot see certain menu items
- Your role doesn't have permission
- Ask super_admin to upgrade your role

### Getting logged out randomly
- Session timeout (2 hours idle) - log in again
- Server restarted - log in again
- Clear browser cache and try again

## 📞 Next Steps

1. **Login** with the credentials above
2. **Explore** the dashboard and menus
3. **Create** some test data
4. **Test** each feature
5. **Invite** team members and assign roles

---

**System Ready!** Your Chabrin Lease Management System is fully set up with user authentication and role-based access control. Happy leasing! 🎉
