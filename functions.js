// --- Student Registration Rows ---
let studentIndex = 1;

document.addEventListener('DOMContentLoaded', () => {
  const studentsWrapper = document.getElementById('studentsWrapper');
  const addRowBtn = document.getElementById('addRow');
  const registerForm = document.getElementById('registerForm');

  if (addRowBtn) {
    addRowBtn.addEventListener('click', () => {
      const row = document.createElement('div');
      row.className = 'row g-2 mb-3 student-row';
      row.innerHTML = `
        <div class="col-md-3"><input type="text" name="students[${studentIndex}][schoolno]" class="form-control" placeholder="StudentSchoolNumber" required></div>
        <div class="col-md-3"><input type="text" name="students[${studentIndex}][name]" class="form-control" placeholder="First Name" required></div>
        <div class="col-md-3"><input type="text" name="students[${studentIndex}][surname]" class="form-control" placeholder="Surname" required></div>
        <div class="col-md-2"><input type="text" name="students[${studentIndex}][grade]" class="form-control" placeholder="Grade" required></div>
        <div class="col-md-1 d-flex align-items-center"><button type="button" class="btn btn-danger btn-sm remove-row" aria-label="Remove student">&times;</button></div>
      `;
      studentsWrapper.appendChild(row);
      studentIndex++;
    });
  }

  studentsWrapper.addEventListener('click', (e) => {
    if (e.target.classList.contains('remove-row')) {
      e.target.closest('.student-row').remove();
    }
  });

  if (registerForm) {
    registerForm.addEventListener('reset', () => {
      studentsWrapper.innerHTML = `
        <div class="row g-2 mb-3 student-row">
          <div class="col-md-3"><input type="text" name="students[0][schoolno]" class="form-control" placeholder="StudentSchoolNumber" required></div>
          <div class="col-md-3"><input type="text" name="students[0][name]" class="form-control" placeholder="First Name" required></div>
          <div class="col-md-3"><input type="text" name="students[0][surname]" class="form-control" placeholder="Surname" required></div>
          <div class="col-md-2"><input type="text" name="students[0][grade]" class="form-control" placeholder="Grade" required></div>
          <div class="col-md-1 d-flex align-items-center"><button type="button" class="btn btn-danger btn-sm remove-row" aria-label="Remove student">&times;</button></div>
        </div>
      `;
      studentIndex = 1;
    });
  }
});

// --- Locker Applications ---
let appIndex = 1;
function addApplication() {
  const container = document.getElementById('lockerContainer');
  const firstSelect = document.querySelector('#lockerContainer select');
  const options = firstSelect ? firstSelect.innerHTML : '<option value="">-- Select --</option>';

  const today = new Date().toISOString().split('T')[0];

  const block = document.createElement('div');
  block.className = 'locker-block mt-3';
  block.innerHTML = `
    <label>Student</label>
    <select name="applications[${appIndex}][student_no]" class="form-control" required>
      ${options}
    </select>
    <label>Booked For</label>
    <input type="date" name="applications[${appIndex}][date]" class="form-control"
           min="${today}" required>
    <button type="button" class="btn btn-sm btn-outline-danger remove-app mt-2" aria-label="Remove application">Remove</button>
  `;
  container.appendChild(block);
  appIndex++;
}

document.addEventListener('DOMContentLoaded', () => {
  const lockerContainer = document.getElementById('lockerContainer');
  const lockerForm = document.getElementById('lockerForm');

  lockerContainer.addEventListener('click', (e) => {
    if (e.target.classList.contains('remove-app')) {
      e.target.closest('.locker-block').remove();
    }
  });

  if (lockerForm) {
    lockerForm.addEventListener('reset', () => {
      const firstSelect = document.querySelector('#lockerContainer select');
      const options = firstSelect ? firstSelect.innerHTML : '<option value="">-- Select --</option>';
      const today = new Date().toISOString().split('T')[0];

      lockerContainer.innerHTML = `
        <div class="locker-block">
          <label>Student</label>
          <select name="applications[0][student_no]" class="form-control" required>${options}</select>
          <label>Booked For</label>
          <input type="date" name="applications[0][date]" class="form-control"
                 min="${today}" required>
        </div>
      `;
      appIndex = 1;
    });
  }
});