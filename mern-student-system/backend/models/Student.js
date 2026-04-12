const mongoose = require('mongoose');
const bcrypt = require('bcryptjs');
const validator = require('validator');

const studentSchema = new mongoose.Schema({
  firstName: {
    type: String,
    required: [true, 'First name is required'],
    trim: true,
    validate: {
      validator: function(v) {
        return /^[A-Za-z\s]+$/.test(v);
      },
      message: 'First name must contain alphabetic characters only'
    }
  },
  lastName: {
    type: String,
    required: [true, 'Last name is required'],
    trim: true,
    validate: {
      validator: function(v) {
        return /^[A-Za-z\s]+$/.test(v);
      },
      message: 'Last name must contain alphabetic characters only'
    }
  },
  rollNo: {
    type: String,
    required: [true, 'Roll number is required'],
    unique: true,
    trim: true
  },
  password: {
    type: String,
    required: [true, 'Password is required'],
    minlength: [6, 'Password must be at least 6 characters']
  },
  contactNumber: {
    type: String,
    required: [true, 'Contact number is required'],
    validate: {
      validator: function(v) {
        return /^\d{10}$/.test(v);
      },
      message: 'Contact number must be exactly 10 digits'
    }
  },
  createdAt: {
    type: Date,
    default: Date.now
  }
});

// Hash password before saving
studentSchema.pre('save', async function() {
  if (!this.isModified('password')) return;
  this.password = await bcrypt.hash(this.password, 12);
});

// Method to verify password
studentSchema.methods.comparePassword = async function(candidatePassword, userPassword) {
  return await bcrypt.compare(candidatePassword, userPassword);
};

const Student = mongoose.model('Student', studentSchema);

module.exports = Student;
