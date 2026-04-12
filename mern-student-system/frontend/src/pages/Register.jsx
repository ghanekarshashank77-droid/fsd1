import React, { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { User, Hash, Lock, Phone, ArrowRight, UserPlus, CheckCircle } from 'lucide-react';
import { api } from '../App';

const Register = () => {
  const navigate = useNavigate();
  const [formData, setFormData] = useState({
    firstName: '',
    lastName: '',
    rollNo: '',
    contactNumber: '',
    password: '',
    confirmPassword: ''
  });
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  const handleChange = (e) => {
    const { name, value } = e.target;
    // For contact number, only allow digits
    if (name === 'contactNumber' && !/^\d*$/.test(value)) return;
    
    setFormData({ ...formData, [name]: value });
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    setLoading(true);

    try {
      await api.post('/register', formData);
      navigate('/login?registered=true');
    } catch (err) {
      setError(err.response?.data?.message || 'Something went wrong. Please try again.');
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
          <UserPlus size={24} color="white" />
        </div>
        <h1>Create Account</h1>
        <p style={{ color: 'var(--text-muted)', fontSize: '0.875rem' }}>Join the student management system</p>
      </div>

      {error && <div className="alert alert-error">{error}</div>}

      <form onSubmit={handleSubmit}>
        <div className="grid-2">
          <div className="input-group">
            <label className="input-label">First Name</label>
            <User className="input-icon" size={18} />
            <input
              type="text"
              name="firstName"
              className="input-field"
              placeholder="John"
              value={formData.firstName}
              onChange={handleChange}
              required
            />
          </div>
          <div className="input-group">
            <label className="input-label">Last Name</label>
            <User className="input-icon" size={18} />
            <input
              type="text"
              name="lastName"
              className="input-field"
              placeholder="Doe"
              value={formData.lastName}
              onChange={handleChange}
              required
            />
          </div>
        </div>

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
          <label className="input-label">Contact Number</label>
          <Phone className="input-icon" size={18} />
          <input
            type="text"
            name="contactNumber"
            className="input-field"
            placeholder="10-digit mobile number"
            maxLength="10"
            value={formData.contactNumber}
            onChange={handleChange}
            required
          />
        </div>

        <div className="grid-2">
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
          <div className="input-group">
            <label className="input-label">Confirm Password</label>
            <Lock className="input-icon" size={18} />
            <input
              type="password"
              name="confirmPassword"
              className="input-field"
              placeholder="••••••"
              value={formData.confirmPassword}
              onChange={handleChange}
              required
            />
          </div>
        </div>

        <button type="submit" className="btn btn-primary mt-4" disabled={loading}>
          {loading ? 'Creating Account...' : (
            <>
              Register Student <ArrowRight size={18} />
            </>
          )}
        </button>
      </form>

      <div className="text-center mt-4">
        <p style={{ fontSize: '0.875rem', color: 'var(--text-muted)' }}>
          Already have an account? <Link to="/login" className="link">Sign In</Link>
        </p>
      </div>
    </div>
  );
};

export default Register;
