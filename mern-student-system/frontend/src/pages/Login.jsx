import React, { useState, useEffect } from 'react';
import { Link, useNavigate, useLocation } from 'react-router-dom';
import { Hash, Lock, LogIn, ArrowRight } from 'lucide-react';
import { api } from '../App';

const Login = ({ onLogin }) => {
  const navigate = useNavigate();
  const location = useLocation();
  const [formData, setFormData] = useState({
    rollNo: '',
    password: ''
  });
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    // Check if redirected from register
    const query = new URLSearchParams(location.search);
    if (query.get('registered')) {
      setSuccess('Account created successfully! Please log in.');
    }
  }, [location]);

  const handleChange = (e) => {
    setFormData({ ...formData, [e.target.name]: e.target.value });
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    setSuccess('');
    setLoading(true);

    try {
      const res = await api.post('/login', formData);
      localStorage.setItem('token', res.data.token);
      onLogin(); // Update parent state
      navigate('/');
    } catch (err) {
      setError(err.response?.data?.message || 'Invalid credentials. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="glass-card" style={{ margin: 'auto' }}>
      <div className="text-center mb-6">
        <div style={{ 
          background: 'linear-gradient(135deg, var(--primary), var(--secondary))',
          width: '50px', height: '50px', borderRadius: '12px',
          display: 'flex', alignItems: 'center', justifyContent: 'center',
          margin: '0 auto 1rem', boxShadow: '0 4px 15px rgba(99, 102, 241, 0.4)'
        }}>
          <LogIn size={24} color="white" />
        </div>
        <h1>Welcome Back</h1>
        <p style={{ color: 'var(--text-muted)', fontSize: '0.875rem' }}>Sign in to access your dashboard</p>
      </div>

      {error && <div className="alert alert-error">{error}</div>}
      {success && <div className="alert alert-success">{success}</div>}

      <form onSubmit={handleSubmit}>
        <div className="input-group">
          <label className="input-label">Roll Number / Student ID</label>
          <Hash className="input-icon" size={18} />
          <input
            type="text"
            name="rollNo"
            className="input-field"
            placeholder="e.g. CS2024001"
            value={formData.rollNo}
            onChange={handleChange}
            required
          />
        </div>

        <div className="input-group">
          <label className="input-label">Password</label>
          <Lock className="input-icon" size={18} />
          <input
            type="password"
            name="password"
            className="input-field"
            placeholder="••••••"
            value={formData.password}
            onChange={handleChange}
            required
          />
        </div>

        <button type="submit" className="btn btn-primary mt-4" disabled={loading}>
          {loading ? 'Authenticating...' : (
            <>
              Sign In <ArrowRight size={18} />
            </>
          )}
        </button>
      </form>

      <div className="text-center mt-4">
        <p style={{ fontSize: '0.875rem', color: 'var(--text-muted)' }}>
          Don't have an account? <Link to="/register" className="link">Register Now</Link>
        </p>
      </div>
    </div>
  );
};

export default Login;
