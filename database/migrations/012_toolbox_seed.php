<?php
declare(strict_types=1);

/**
 * Phase 7 seed: a starter list of well-known FREE and legitimate resources (official sites, open textbooks, open-source software,
 * public data). Everything is editable or deletable in Admin → Free tools. Use Admin → Free tools → "Check links" to verify them.
 * Only add links to the owners' own pages: no pirated books, cracked software or unauthorised copies.
 */
return function (PDO $pdo): void {
    if ((int)$pdo->query('SELECT COUNT(*) FROM toolbox_links')->fetchColumn() > 0) {
        return;
    }
    $note = "*Starter notes: always follow the official guide for your version.*\n\n";
    $wrfNotes = $note . "**Before you start**\n\n- WRF runs on Linux. On Windows use **WSL 2** (Windows Subsystem for Linux) with Ubuntu.\n- You need a Fortran and C compiler (`gfortran`, `gcc`), `make`, `perl`, `tcsh`, and the **netCDF** libraries (C and Fortran, built with the same compilers). For parallel runs also install MPI (for example OpenMPI).\n\n**Main steps**\n\n1. Download **WRF** and **WPS** (the pre-processing system) from the official GitHub pages.\n2. Set the `NETCDF` environment variable, run `./configure` in the WRF folder (a common choice is the gfortran *dmpar* option), then `./compile em_real`.\n3. In the WPS folder run `./configure` and `./compile`. Download the static geographical data and your forcing data (for example ERA5 or GFS).\n4. Run `geogrid.exe`, `ungrib.exe`, `metgrid.exe`, then in the WRF run folder `real.exe` and `wrf.exe`.\n\nThe **online tutorial** linked on this page walks through every step with sample data.";
    $qeNotes = $note . "**Getting it**\n\n- On Ubuntu or WSL: `sudo apt install quantum-espresso` (an easy start), or download the source from the official site and compile it.\n- You need **pseudopotential** files for every element in your calculation. The official site links to libraries of them.\n\n**A first calculation**\n\n1. Write an input file for `pw.x` (structure, k-points, cut-offs, pseudopotentials).\n2. Run: `mpirun -np 4 pw.x -in si.scf.in > si.scf.out`\n3. Read the total energy and convergence in the output file. Then follow the tutorials for band structures, relaxations and phonons.";
    $pyNotes = $note . "A good, free setup for meteorology and physics work:\n\n1. Install **Miniforge** (conda) from the link on this page.\n2. Create an environment: `conda create -n sky python=3.12 numpy scipy matplotlib xarray netcdf4 metpy cartopy jupyterlab`\n3. Activate it with `conda activate sky` and start notebooks with `jupyter lab`.";
    $L = fn(string $title, string $url, string $kind, string $subject, string $aud, string $provider, string $desc, string $lic, ?string $notes = null, int $feat = 0) => [$title, $url, $kind, $subject, $aud, $provider, $desc, $lic, $notes, $feat];

    $rows = [
        // video
        $L('MIT OpenCourseWare on YouTube', 'https://www.youtube.com/@mitocw', 'video', 'Physics', 'all', 'MIT OpenCourseWare', 'Complete university lecture series from MIT, including physics, mathematics and earth sciences.', 'free', null, 1),
        $L('Khan Academy on YouTube', 'https://www.youtube.com/@khanacademy', 'video', 'Mathematics', 'students', 'Khan Academy', 'Clear short lessons from arithmetic to calculus, plus physics and chemistry basics.', 'free', null, 1),
        $L('3Blue1Brown on YouTube', 'https://www.youtube.com/@3blue1brown', 'video', 'Mathematics', 'all', '3Blue1Brown', 'Beautiful visual explanations of calculus, linear algebra, differential equations and more.', 'free', null, 1),
        $L('Professor Leonard on YouTube', 'https://www.youtube.com/@ProfessorLeonard', 'video', 'Mathematics', 'students', 'Professor Leonard', 'Full-length university lectures in calculus, statistics and differential equations.', 'free'),
        $L('The Organic Chemistry Tutor on YouTube', 'https://www.youtube.com/@TheOrganicChemistryTutor', 'video', 'Physics', 'students', 'The Organic Chemistry Tutor', 'Thousands of worked problems in physics, maths and chemistry, step by step.', 'free'),
        $L('Veritasium on YouTube', 'https://www.youtube.com/@veritasium', 'video', 'Physics', 'all', 'Veritasium', 'Science and physics stories and experiments explained for a wide audience.', 'free'),
        // courses and books
        $L('The Feynman Lectures on Physics (online)', 'https://www.feynmanlectures.caltech.edu/', 'course', 'Physics', 'students', 'Caltech', 'The classic physics lectures, free to read online in your browser.', 'read'),
        $L('OpenStax: free textbooks', 'https://openstax.org/subjects', 'course', 'Physics', 'students', 'OpenStax (Rice University)', 'Peer-reviewed, openly licensed textbooks including University Physics, Calculus and Chemistry.', 'open', null, 1),
        $L('LibreTexts', 'https://libretexts.org/', 'course', 'General', 'all', 'LibreTexts', 'A huge library of free open textbooks in physics, mathematics, geosciences and more.', 'open'),
        $L('MIT OpenCourseWare', 'https://ocw.mit.edu/', 'course', 'Physics', 'all', 'MIT', 'Free course materials from MIT: lecture notes, problem sets, exams and videos.', 'free', null, 1),
        $L('NPTEL', 'https://nptel.ac.in/', 'course', 'General', 'all', 'NPTEL (India)', 'Free video courses from the Indian Institutes of Technology in physics, mathematics and engineering.', 'free'),
        $L("Paul's Online Math Notes", 'https://tutorial.math.lamar.edu/', 'course', 'Mathematics', 'students', 'Lamar University', 'Clear notes with worked examples for calculus, differential equations and linear algebra.', 'free'),
        $L('Open Textbook Library', 'https://open.umn.edu/opentextbooks', 'course', 'General', 'all', 'University of Minnesota', 'Browse hundreds of free, openly licensed textbooks that you can read online or download.', 'open'),
        $L('Directory of Open Access Books', 'https://www.doabooks.org/', 'course', 'General', 'researchers', 'DOAB', 'Search peer-reviewed academic books that publishers have made free to read.', 'open'),
        $L('Project Gutenberg', 'https://www.gutenberg.org/', 'course', 'General', 'all', 'Project Gutenberg', 'Over 70,000 books whose copyright has expired, free to read and download.', 'read'),
        $L('COMET MetEd: meteorology training', 'https://www.meted.ucar.edu/', 'course', 'Meteorology & climate', 'all', 'UCAR COMET Program', 'Free professional-level lessons in weather, climate, forecasting and numerical models.', 'account', null, 1),
        $L('NWS JetStream online weather school', 'https://www.weather.gov/jetstream/', 'course', 'Meteorology & climate', 'students', 'US National Weather Service', 'A friendly introduction to the atmosphere, weather, storms and forecasting.', 'free'),
        $L('Climate.gov', 'https://www.climate.gov/', 'course', 'Meteorology & climate', 'all', 'NOAA', 'Explainers, maps and teaching resources on climate science.', 'free'),
        $L('NASA Climate', 'https://climate.nasa.gov/', 'course', 'Meteorology & climate', 'all', 'NASA', 'Evidence, data visualisations and learning resources about Earth\'s changing climate.', 'free'),
        // online tools
        $L('PhET Interactive Simulations', 'https://phet.colorado.edu/', 'online', 'Teaching', 'teachers', 'University of Colorado Boulder', 'Free interactive physics, maths and chemistry simulations for classroom and self-study.', 'free', null, 1),
        $L('Desmos Graphing Calculator', 'https://www.desmos.com/calculator', 'online', 'Mathematics', 'all', 'Desmos', 'A fast, free online graphing calculator.', 'free'),
        $L('GeoGebra', 'https://www.geogebra.org/', 'online', 'Mathematics', 'all', 'GeoGebra', 'Free tools for graphing, geometry, algebra, calculus and 3D.', 'free'),
        $L('Wolfram MathWorld', 'https://mathworld.wolfram.com/', 'online', 'Mathematics', 'all', 'Wolfram Research', 'An encyclopedia of mathematics: definitions, formulas and diagrams.', 'free'),
        $L('Overleaf (online LaTeX editor)', 'https://www.overleaf.com/', 'online', 'Research skills', 'researchers', 'Overleaf', 'Write papers and theses in LaTeX in your browser, with templates for many journals.', 'plan'),
        // software
        $L('WRF: Weather Research and Forecasting model', 'https://www.mmm.ucar.edu/models/wrf', 'software', 'Meteorology & climate', 'researchers', 'NCAR / UCAR', 'The widely used open-source regional weather and climate model. Source code, documentation and user support.', 'open', $wrfNotes, 1),
        $L('WRF source code (GitHub)', 'https://github.com/wrf-model/WRF', 'software', 'Meteorology & climate', 'researchers', 'WRF model developers', 'Official WRF repository and releases.', 'open'),
        $L('WRF Online Tutorial', 'https://www2.mmm.ucar.edu/wrf/OnLineTutorial/index.php', 'tutorial', 'Meteorology & climate', 'researchers', 'NCAR / UCAR', 'Step-by-step official tutorial: compiling WRF and WPS, preparing data and running your first case.', 'free', null, 1),
        $L('WRF user support and documentation', 'https://www2.mmm.ucar.edu/wrf/users/', 'tutorial', 'Meteorology & climate', 'researchers', 'NCAR / UCAR', 'User guides, download pages, FAQs and forum for WRF and WPS.', 'free'),
        $L('wrf-python', 'https://wrf-python.readthedocs.io/', 'software', 'Meteorology & climate', 'researchers', 'NCAR', 'Python package for reading, diagnosing and plotting WRF output.', 'open'),
        $L('MPAS (Model for Prediction Across Scales)', 'https://mpas-dev.github.io/', 'software', 'Meteorology & climate', 'researchers', 'NCAR / LANL', 'Open-source global and regional atmosphere and ocean models.', 'open'),
        $L('Community Earth System Model (CESM)', 'https://www.cesm.ucar.edu/', 'software', 'Meteorology & climate', 'researchers', 'NCAR', 'A fully coupled, community climate model with public releases and tutorials.', 'open'),
        $L('Quantum ESPRESSO', 'https://www.quantum-espresso.org/', 'software', 'Physics', 'researchers', 'Quantum ESPRESSO Foundation', 'Open-source suite for electronic-structure calculations and materials modelling.', 'open', $qeNotes, 1),
        $L('Quantum ESPRESSO: learn and tutorials', 'https://www.quantum-espresso.org/learn/', 'tutorial', 'Physics', 'researchers', 'Quantum ESPRESSO Foundation', 'Official learning page: tutorials, user guide and material from schools and workshops.', 'free'),
        $L('LAMMPS molecular dynamics', 'https://www.lammps.org/', 'software', 'Physics', 'researchers', 'Sandia National Laboratories', 'Open-source classical molecular-dynamics simulator, with manual and examples.', 'open'),
        $L('OpenFOAM', 'https://www.openfoam.com/', 'software', 'Physics', 'researchers', 'OpenFOAM', 'Open-source computational fluid dynamics toolbox.', 'open'),
        $L('Python', 'https://www.python.org/downloads/', 'software', 'Computing & programming', 'all', 'Python Software Foundation', 'The programming language most used in science and data analysis.', 'open'),
        $L('Miniforge (conda for scientific Python)', 'https://github.com/conda-forge/miniforge', 'software', 'Computing & programming', 'researchers', 'conda-forge', 'A minimal, free installer for managing scientific Python environments.', 'open', $pyNotes),
        $L('Jupyter', 'https://jupyter.org/', 'software', 'Computing & programming', 'all', 'Project Jupyter', 'Notebooks that combine code, results and notes: ideal for teaching and research.', 'open'),
        $L('NumPy', 'https://numpy.org/', 'software', 'Computing & programming', 'all', 'NumPy', 'Fast array computing for Python.', 'open'),
        $L('SciPy', 'https://scipy.org/', 'software', 'Computing & programming', 'all', 'SciPy', 'Scientific computing library: integration, optimisation, statistics and more.', 'open'),
        $L('Matplotlib', 'https://matplotlib.org/', 'software', 'Computing & programming', 'all', 'Matplotlib', 'Publication-quality plots in Python.', 'open'),
        $L('xarray', 'https://xarray.dev/', 'software', 'Meteorology & climate', 'researchers', 'xarray', 'Work with labelled multi-dimensional data such as netCDF climate files.', 'open'),
        $L('MetPy', 'https://unidata.github.io/MetPy/', 'software', 'Meteorology & climate', 'researchers', 'Unidata', 'Python toolkit for meteorological calculations, skew-T diagrams and plotting.', 'open'),
        $L('Cartopy', 'https://scitools.org.uk/cartopy/', 'software', 'Meteorology & climate', 'researchers', 'SciTools', 'Draw maps and project geospatial data in Python.', 'open'),
        $L('GNU Octave', 'https://octave.org/', 'software', 'Computing & programming', 'all', 'GNU Octave', 'A free alternative to MATLAB for numerical computing.', 'open'),
        $L('R', 'https://www.r-project.org/', 'software', 'Computing & programming', 'researchers', 'The R Foundation', 'Free language and environment for statistics and graphics.', 'open'),
        $L('Julia', 'https://julialang.org/', 'software', 'Computing & programming', 'researchers', 'JuliaLang', 'A fast, free language for scientific computing.', 'open'),
        $L('SageMath', 'https://www.sagemath.org/', 'software', 'Mathematics', 'all', 'SageMath', 'Free open-source mathematics system: algebra, calculus, number theory and more.', 'open'),
        $L('Maxima', 'https://maxima.sourceforge.io/', 'software', 'Mathematics', 'all', 'Maxima', 'Free computer algebra system for symbolic mathematics.', 'open'),
        $L('Panoply netCDF viewer', 'https://www.giss.nasa.gov/tools/panoply/', 'software', 'Meteorology & climate', 'all', 'NASA GISS', 'Quickly plot and explore netCDF, HDF and GRIB data without programming.', 'free'),
        $L('CDO: Climate Data Operators', 'https://code.mpimet.mpg.de/projects/cdo', 'software', 'Meteorology & climate', 'researchers', 'Max Planck Institute for Meteorology', 'Command-line tools for processing climate and forecast data.', 'open'),
        $L('ParaView', 'https://www.paraview.org/', 'software', 'Computing & programming', 'researchers', 'Kitware', 'Open-source visualisation of large scientific datasets.', 'open'),
        $L('TeX Live (LaTeX for your computer)', 'https://www.tug.org/texlive/', 'software', 'Research skills', 'researchers', 'TeX Users Group', 'A complete free LaTeX distribution for Windows, macOS and Linux.', 'open'),
        $L('Zotero', 'https://www.zotero.org/', 'software', 'Research skills', 'researchers', 'Zotero', 'Free reference manager: collect, cite and share your sources.', 'open'),
        $L('LibreOffice', 'https://www.libreoffice.org/', 'software', 'Research skills', 'all', 'The Document Foundation', 'A free office suite for documents, spreadsheets and presentations.', 'open'),
        $L('Git', 'https://git-scm.com/', 'software', 'Computing & programming', 'researchers', 'Git', 'Version control for your code and data analysis.', 'open'),
        $L('Visual Studio Code', 'https://code.visualstudio.com/', 'software', 'Computing & programming', 'all', 'Microsoft', 'A free code editor that works well for Python, Fortran and LaTeX.', 'free'),
        $L('Install Linux on Windows with WSL', 'https://learn.microsoft.com/windows/wsl/install', 'tutorial', 'Computing & programming', 'researchers', 'Microsoft Learn', 'How to run Ubuntu inside Windows: the easiest way to use WRF, Quantum ESPRESSO and other Linux-only models.', 'free', null, 1),
        // data
        $L('Copernicus Climate Data Store (ERA5)', 'https://cds.climate.copernicus.eu/', 'data', 'Meteorology & climate', 'researchers', 'ECMWF / Copernicus', 'Free reanalysis and climate datasets, including ERA5, to drive models like WRF.', 'account', null, 1),
        $L('NASA Earthdata', 'https://www.earthdata.nasa.gov/', 'data', 'Meteorology & climate', 'researchers', 'NASA', 'Satellite and Earth-science data from NASA missions.', 'account'),
        $L('NOAA National Centers for Environmental Information', 'https://www.ncei.noaa.gov/', 'data', 'Meteorology & climate', 'researchers', 'NOAA', 'Weather, climate and ocean archives.', 'free'),
        $L('NASA POWER', 'https://power.larc.nasa.gov/', 'data', 'Meteorology & climate', 'all', 'NASA', 'Solar and meteorological data for any location, useful for agriculture and energy studies.', 'free'),
        $L('CHIRPS rainfall data', 'https://www.chc.ucsb.edu/data/chirps', 'data', 'Meteorology & climate', 'researchers', 'Climate Hazards Center, UC Santa Barbara', 'Long rainfall records for Africa and the tropics, widely used in drought and monsoon research.', 'free'),
        $L('KNMI Climate Explorer', 'https://climexp.knmi.nl/', 'data', 'Meteorology & climate', 'researchers', 'KNMI', 'Analyse climate data online without installing anything.', 'free'),
        $L('Open-Meteo', 'https://open-meteo.com/', 'data', 'Meteorology & climate', 'all', 'Open-Meteo', 'Free weather and climate API for non-commercial use.', 'free'),
        $L('ESGF: CMIP climate model data', 'https://esgf.llnl.gov/', 'data', 'Meteorology & climate', 'researchers', 'Earth System Grid Federation', 'Access to CMIP climate model output.', 'account'),
        // papers
        $L('arXiv', 'https://arxiv.org/', 'papers', 'Physics', 'researchers', 'Cornell University', 'Free preprints in physics, mathematics, computer science and more.', 'free', null, 1),
        $L('Semantic Scholar', 'https://www.semanticscholar.org/', 'papers', 'Research skills', 'researchers', 'Allen Institute for AI', 'Free AI-assisted search engine for scientific papers.', 'free'),
        $L('DOAJ: Directory of Open Access Journals', 'https://doaj.org/', 'papers', 'Research skills', 'researchers', 'DOAJ', 'Find quality journals and articles that are free to read.', 'free'),
        $L('Unpaywall', 'https://unpaywall.org/', 'papers', 'Research skills', 'researchers', 'OurResearch', 'Finds the legal free version of a paper when one exists.', 'free'),
        $L('CORE', 'https://core.ac.uk/', 'papers', 'Research skills', 'researchers', 'The Open University', 'The world\'s largest collection of open-access research papers.', 'free'),
        $L('Zenodo', 'https://zenodo.org/', 'papers', 'Research skills', 'researchers', 'CERN', 'Share and find research papers, data and software, each with a citable DOI.', 'free'),
        $L('African Journals Online (AJOL)', 'https://www.ajol.info/', 'papers', 'Research skills', 'researchers', 'AJOL', 'Journals published in Africa, many free to read.', 'free'),
        $L('Research4Life', 'https://www.research4life.org/', 'papers', 'Research skills', 'researchers', 'Research4Life', 'Free or low-cost access to journals and books for institutions in eligible countries (ask your library).', 'eligible'),
        $L('ORCID', 'https://orcid.org/', 'papers', 'Research skills', 'researchers', 'ORCID', 'A free persistent identifier that connects you to your publications.', 'free'),
    ];
    $ins = $pdo->prepare('INSERT INTO toolbox_links (title, url, kind, subject, audience, provider, description, license, notes, is_featured, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    foreach ($rows as $i => [$title, $url, $kind, $subject, $aud, $provider, $desc, $lic, $notes, $feat]) {
        $ins->execute([$title, $url, $kind, $subject, $aud, $provider, $desc, $lic, $notes, $feat, ($i + 1) * 10]);
    }
};
