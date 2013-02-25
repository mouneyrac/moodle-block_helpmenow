Moodle Block: Helpmenow 
Copyright: VLACS 2013 www.vlacs.org
License: http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

Intro:
This moodle block creates a chat interface for students and teachers to connect.
It also provides a help desk chat interface for students to ask questions of the
teachers/admins who are assigned as helpers and currently logged in. It also has plugins
for multiple sharing tools that can be enabled.

Requires:
Client side javascript

Features:

- Help Desk Chat:
  - Admins can create any number of help desks, ie: Technical Help, Academic
    Help etc. 
  - Users are added as helpers to the help desk queues by adding permissions for them then
    assigning them to helpers using the Manage Queues Link (Admins only)
  - These queues are designed so that multiple teachers/admins can login to help
    students with questions during specific hours.

- Teacher/Student Chat:
  - Lists are managed with personalized contact_list plugin system (must be
    customized to your needs)

- Teachers and help desk helpers are not automatically logged into the system
  when they use moodle, they should log in during the times they are available
  to answer students questions. All students are automatically logged into the
  helpmenow block

- Teachers may enter a Message of the Day (MOTD) which is their current status,
  this is displayed to students.

- Plugins to enable sharing tools
  - Adobe Connect
  - WizIQ
  - GoToMeeting (base code provided, has not been used in a while)

  - Links are provided in chat to invite students to join the teacher in the
    shared area.

- Chats that are missed are emailed to the user.

- Use block settings to change the name of the block to personalize

- Updates are not instantaneous, things may take up to 10 seconds to refresh
  (like logging in and our of the block, lists of students online)

- Admins may see "Who's Here" which shows a list of all teachers and their
  status and if they are online. It also shows which helpers are currently
  staffing the help desk.

- This is designed to not allow students to chat with other students. However it
  is dependant on how the contact_lists are setup.


