import React, { useState, useEffect } from 'react';
import { LogOut, User, Hash, Phone, Calendar, GraduationCap, Edit, Trash2 } from 'lucide-react';
import { api } from '../App';

const Dashboard = ({ onLogout }) => {
  const [currentUser, setCurrentUser] = useState(null);
  const [students, setStudents] = useState([]);
  const [loading, setLoading] = useState(true);
  const [editingStudent, setEditingStudent] = useState(null);
  const [error, setError] = useState('');

  useEffect(() => {
    fetchData();
  }, []);

  const fetchData = async () => {
    try {
      const [uRes, sRes] = await Promise.all([
        api.get('/me'),
        api.get('/students')
      ]);
      setCurrentUser(uRes.data.data.student);
      setStudents(sRes.data.data.students);
    } catch (err) {
      console.error('Failed to fetch data', err);
      setError('Failed to load students list');
    } finally {
      setLoading(false);
    }
  };

  const handleLogout = () => {
    localStorage.removeItem('token');
    onLogout();
  };

  const handleDelete = async (id) => {
    if (!window.confirm('Are you sure you want to delete this student?')) return;
    try {
      await api.delete(`/students/${id}`);
      setStudents(students.filter(s => s._id !== id));
    } catch (err) {
      alert('Failed to delete student');
    }
  };

  const handleUpdate = async (e) => {
    e.preventDefault();
    try {
      const res = await api.patch(`/students/${editingStudent._id}`, editingStudent);
      setStudents(students.map(s => s._id === editingStudent._id ? res.data.data.student : s));
      setEditingStudent(null);
      alert('Student updated successfully');
    } catch (err) {
      alert(err.response?.data?.message || 'Failed to update student');
    }
  };

  if (loading) return (
    <div style={{ display: 'flex', justifyContent: 'center', alignItems: 'center', height: '100vh' }}>
      <p>Loading Dashboard...</p>
    </div>
  );

  return (
    <div className="container" style={{ maxWidth: '1000px', margin: '2rem auto', padding: '0 1rem' }}>
      {/* Profile Header */}
      <div className="glass-card mb-6" style={{ maxWidth: '100%' }}>
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: '1rem' }}>
            <div style={{ 
              width: '50px', height: '50px', borderRadius: '12px',
              background: 'linear-gradient(135deg, var(--primary), var(--secondary))',
              display: 'flex', alignItems: 'center', justifyContent: 'center'
            }}>
              <GraduationCap size={24} color="white" />
            </div>
            <div>
              <h1 style={{ fontSize: '1.25rem' }}>{currentUser.firstName} {currentUser.lastName}</h1>
              <p style={{ color: 'var(--text-muted)', fontSize: '0.8rem' }}>Welcome to Management Dashboard</p>
            </div>
          </div>
          <button onClick={handleLogout} className="btn" style={{ 
            width: 'auto', padding: '0.5rem 1rem', background: 'rgba(239, 68, 68, 0.1)', color: '#f87171', border: '1px solid rgba(239, 68, 68, 0.2)'
          }}>
            <LogOut size={16} /> Logout
          </button>
        </div>
      </div>

      {/* Management Table */}
      <div className="glass-card" style={{ maxWidth: '100%', overflowX: 'auto' }}>
        <div className="mb-6" style={{ display: 'flex', alignItems: 'center', gap: '0.5rem' }}>
          <User size={20} color="var(--primary)" />
          <h2 style={{ fontSize: '1.2rem' }}>Manage Registered Students</h2>
        </div>

        {error && <div className="alert alert-error">{error}</div>}

        <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: '0.9rem' }}>
          <thead>
            <tr style={{ color: 'var(--text-muted)', borderBottom: '1px solid var(--border)' }}>
              <th style={{ textAlign: 'left', padding: '1rem' }}>Name</th>
              <th style={{ textAlign: 'left', padding: '1rem' }}>Roll No</th>
              <th style={{ textAlign: 'left', padding: '1rem' }}>Contact</th>
              <th style={{ textAlign: 'right', padding: '1rem' }}>Actions</th>
            </tr>
          </thead>
          <tbody>
            {students.map(student => (
              <tr key={student._id} style={{ borderBottom: '1px solid var(--border)', transition: 'background 0.2s' }} className="table-row">
                <td style={{ padding: '1rem' }}>{student.firstName} {student.lastName}</td>
                <td style={{ padding: '1rem' }}>{student.rollNo}</td>
                <td style={{ padding: '1rem' }}>{student.contactNumber}</td>
                <td style={{ padding: '1rem', textAlign: 'right', display: 'flex', justifyContent: 'flex-end', gap: '0.5rem' }}>
                  <button 
                    onClick={() => setEditingStudent(student)}
                    className="btn" 
                    style={{ width: 'auto', padding: '0.4rem', background: 'rgba(99, 102, 241, 0.1)', color: 'var(--primary)', border: '1px solid rgba(99, 102, 241, 0.2)' }}
                  >
                    <Edit size={16} />
                  </button>
                  <button 
                    onClick={() => handleDelete(student._id)}
                    className="btn" 
                    style={{ width: 'auto', padding: '0.4rem', background: 'rgba(239, 68, 68, 0.1)', color: '#f87171', border: '1px solid rgba(239, 68, 68, 0.2)' }}
                  >
                    <Trash2 size={16} />
                  </button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      {/* Edit Modal */}
      {editingStudent && (
        <div style={{
          position: 'fixed', inset: 0, background: 'rgba(15, 23, 42, 0.8)',
          display: 'flex', alignItems: 'center', justifyContent: 'center', zIndex: 100,
          backdropFilter: 'blur(4px)'
        }}>
          <div className="glass-card" style={{ maxWidth: '400px' }}>
            <h3 className="mb-4">Edit Student</h3>
            <form onSubmit={handleUpdate}>
              <div className="input-group">
                <label className="input-label">First Name</label>
                <input 
                  type="text" className="input-field" style={{ paddingLeft: '1rem' }}
                  value={editingStudent.firstName}
                  onChange={e => setEditingStudent({...editingStudent, firstName: e.target.value})}
                  required
                />
              </div>
              <div className="input-group">
                <label className="input-label">Last Name</label>
                <input 
                  type="text" className="input-field" style={{ paddingLeft: '1rem' }}
                  value={editingStudent.lastName}
                  onChange={e => setEditingStudent({...editingStudent, lastName: e.target.value})}
                  required
                />
              </div>
              <div className="input-group">
                <label className="input-label">Roll Number</label>
                <input 
                  type="text" className="input-field" style={{ paddingLeft: '1rem' }}
                  value={editingStudent.rollNo}
                  onChange={e => setEditingStudent({...editingStudent, rollNo: e.target.value})}
                  required
                />
              </div>
              <div className="input-group">
                <label className="input-label">Contact Number</label>
                <input 
                  type="text" className="input-field" style={{ paddingLeft: '1rem' }}
                  value={editingStudent.contactNumber}
                  onChange={e => setEditingStudent({...editingStudent, contactNumber: e.target.value})}
                  required
                />
              </div>
              <div style={{ display: 'flex', gap: '1rem', marginTop: '1.5rem' }}>
                <button type="button" onClick={() => setEditingStudent(null)} className="btn" style={{ background: 'transparent', border: '1px solid var(--border)' }}>
                  Cancel
                </button>
                <button type="submit" className="btn btn-primary">
                  Save Changes
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};

export default Dashboard;
