import { AuthProvider, useAuth } from './context/AuthContext';
import AuthPage from './components/AuthPage';
import Dashboard from './components/Dashboard';

function AppContent() {
  const { isAuthenticated } = useAuth();

  return isAuthenticated ? <Dashboard /> : <AuthPage />;
}

function App() {
  return (
    <AuthProvider>

      <AppContent />

    </AuthProvider>
  );
}

export default App;

