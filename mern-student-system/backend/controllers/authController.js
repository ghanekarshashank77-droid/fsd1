const Student = require('../models/Student');
const jwt = require('jsonwebtoken');

const signToken = (id) => {
  return jwt.sign({ id }, process.env.JWT_SECRET, {
    expiresIn: '30d'
  });
};

exports.register = async (req, res) => {
  try {
    const { firstName, lastName, rollNo, password, confirmPassword, contactNumber } = req.body;

    // Check if passwords match
    if (password !== confirmPassword) {
      return res.status(400).json({
        status: 'fail',
        message: 'Passwords do not match'
      });
    }

    const newStudent = await Student.create({
      firstName,
      lastName,
      rollNo,
      password,
      contactNumber
    });

    // Remove password from output
    newStudent.password = undefined;

    const token = signToken(newStudent._id);

    res.status(201).json({
      status: 'success',
      token,
      data: {
        student: newStudent
      }
    });
  } catch (err) {
    // Handle uniqueness error (Duplicate Roll No)
    if (err.code === 11000) {
      return res.status(400).json({
        status: 'fail',
        message: 'Roll number already exists'
      });
    }

    res.status(400).json({
      status: 'fail',
      message: err.message
    });
  }
};

exports.login = async (req, res) => {
  try {
    const { rollNo, password } = req.body;

    // Check if rollNo and password exist
    if (!rollNo || !password) {
      return res.status(400).json({
        status: 'fail',
        message: 'Please provide roll number and password'
      });
    }

    // Check if student exists & password is correct
    const student = await Student.findOne({ rollNo }).select('+password');

    if (!student || !(await student.comparePassword(password, student.password))) {
      return res.status(401).json({
        status: 'fail',
        message: 'Incorrect roll number or password'
      });
    }

    // If everything ok, send token to client
    const token = signToken(student._id);

    // Remove password from output
    student.password = undefined;

    res.status(200).json({
      status: 'success',
      token,
      data: {
        student
      }
    });
  } catch (err) {
    res.status(400).json({
      status: 'fail',
      message: err.message
    });
  }
};

// Middleware to protect routes (optional for this task but good practice)
exports.protect = async (req, res, next) => {
  try {
    let token;
    if (req.headers.authorization && req.headers.authorization.startsWith('Bearer')) {
      token = req.headers.authorization.split(' ')[1];
    }

    if (!token) {
      return res.status(401).json({
        status: 'fail',
        message: 'You are not logged in! Please log in to get access.'
      });
    }

    const decoded = jwt.verify(token, process.env.JWT_SECRET);
    const currentStudent = await Student.findById(decoded.id);

    if (!currentStudent) {
      return res.status(401).json({
        status: 'fail',
        message: 'The student belonging to this token no longer exists.'
      });
    }

    req.user = currentStudent;
    next();
  } catch (err) {
    res.status(401).json({
      status: 'fail',
      message: 'Invalid token'
    });
  }
};

exports.getMe = (req, res) => {
  res.status(200).json({
    status: 'success',
    data: {
      student: req.user
    }
  });
};

// CRUD: Get all students
exports.getAllStudents = async (req, res) => {
  try {
    const students = await Student.find().sort('-createdAt');
    res.status(200).json({
      status: 'success',
      results: students.length,
      data: {
        students
      }
    });
  } catch (err) {
    res.status(400).json({
      status: 'fail',
      message: err.message
    });
  }
};

// CRUD: Update student
exports.updateStudent = async (req, res) => {
  try {
    const { firstName, lastName, rollNo, contactNumber } = req.body;
    
    // Find and update
    const student = await Student.findByIdAndUpdate(
      req.params.id, 
      { firstName, lastName, rollNo, contactNumber },
      { new: true, runValidators: true }
    );

    if (!student) {
      return res.status(404).json({
        status: 'fail',
        message: 'No student found with that ID'
      });
    }

    res.status(200).json({
      status: 'success',
      data: {
        student
      }
    });
  } catch (err) {
    if (err.code === 11000) {
      return res.status(400).json({
        status: 'fail',
        message: 'Roll number already exists'
      });
    }
    res.status(400).json({
      status: 'fail',
      message: err.message
    });
  }
};

// CRUD: Delete student
exports.deleteStudent = async (req, res) => {
  try {
    const student = await Student.findByIdAndDelete(req.params.id);

    if (!student) {
      return res.status(404).json({
        status: 'fail',
        message: 'No student found with that ID'
      });
    }

    res.status(204).json({
      status: 'success',
      data: null
    });
  } catch (err) {
    res.status(400).json({
      status: 'fail',
      message: err.message
    });
  }
};
