import { createBrowserRouter, Navigate } from 'react-router';
import { RequireAuth } from './auth/RequireAuth';
import { Layout } from './components/Layout';
import { Login } from './pages/Login';
import { Register } from './pages/Register';
import { ForgotPassword } from './pages/ForgotPassword';
import { PasswordReset } from './pages/PasswordReset';
import { EmailVerification } from './pages/EmailVerification';
import { VerifyEmailCallback } from './pages/VerifyEmailCallback';
import { Dashboard } from './pages/Dashboard';
import { Chat } from './pages/Chat';
import { Announcements } from './pages/Announcements';
import { Wiki } from './pages/Wiki';
import { Schedule } from './pages/Schedule';
import { Tasks } from './pages/Tasks';
import { Files } from './pages/Files';
import { Workflow } from './pages/Workflow';
import { Attendance } from './pages/Attendance';
import { Payroll } from './pages/Payroll';
import { Search } from './pages/Search';
import { Users } from './pages/Users';
import { Groups } from './pages/Groups';

export const router = createBrowserRouter([
  {
    path: '/login',
    element: <Login />,
  },
  {
    path: '/register',
    element: <Register />,
  },
  {
    path: '/forgot-password',
    element: <ForgotPassword />,
  },
  {
    path: '/password-reset',
    element: <PasswordReset />,
  },
  {
    path: '/email-verification',
    element: (
      <RequireAuth>
        <EmailVerification />
      </RequireAuth>
    ),
  },
  {
    path: '/verify-email/:id/:hash',
    element: <VerifyEmailCallback />,
  },
  {
    path: '/',
    element: (
      <RequireAuth>
        <Layout />
      </RequireAuth>
    ),
    children: [
      {
        index: true,
        element: <Navigate to="/dashboard" replace />,
      },
      {
        path: 'dashboard',
        element: <Dashboard />,
      },
      {
        path: 'chat',
        element: <Chat />,
      },
      {
        path: 'announcements',
        element: <Announcements />,
      },
      {
        path: 'wiki',
        element: <Wiki />,
      },
      {
        path: 'schedule',
        element: <Schedule />,
      },
      {
        path: 'tasks',
        element: <Tasks />,
      },
      {
        path: 'files',
        element: <Files />,
      },
      {
        path: 'workflow',
        element: <Workflow />,
      },
      {
        path: 'attendance',
        element: <Attendance />,
      },
      {
        path: 'payroll',
        element: <Payroll />,
      },
      {
        path: 'search',
        element: <Search />,
      },
      {
        path: 'users',
        element: <Users />,
      },
      {
        path: 'groups',
        element: <Groups />,
      },
    ],
  },
]);
